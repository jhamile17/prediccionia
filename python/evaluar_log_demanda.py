import pandas as pd
import numpy as np

from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score


DATASET_PATH = "storage/app/datasets/dataset_demanda.csv"

FEATURES = [
    "producto_id",
    "categoria_id",
    "demanda_anterior",
    "demanda_7_dias",
    "demanda_14_dias",
    "promedio_7_dias",
    "promedio_30_dias",
    "dia_semana",
    "mes",
    "año",
    "es_fin_de_semana",
    "es_dia_especial",
]

TARGET = "demanda"


def crear_modelo():
    return RandomForestRegressor(
        n_estimators=400,
        max_depth=20,
        min_samples_leaf=2,
        max_features="sqrt",
        random_state=42,
        n_jobs=-1,
    )


def evaluar_modelo(
    nombre,
    X_train,
    y_train,
    X_test,
    y_test,
    log_target=False,
):
    modelo = crear_modelo()

    if log_target:
        y_train_modelo = np.log1p(y_train)
    else:
        y_train_modelo = y_train

    modelo.fit(
        X_train,
        y_train_modelo,
    )

    pred = modelo.predict(X_test)

    if log_target:
        pred = np.expm1(pred)

    pred = np.maximum(pred, 0)

    mae = mean_absolute_error(
        y_test,
        pred,
    )

    rmse = np.sqrt(
        mean_squared_error(
            y_test,
            pred,
        )
    )

    r2 = r2_score(
        y_test,
        pred,
    )

    resultado = {
        "modelo": nombre,
        "mae": mae,
        "rmse": rmse,
        "r2": r2,
        "predicciones": pred,
    }

    print()
    print("=" * 60)
    print(nombre)
    print("=" * 60)
    print(f"MAE  : {mae:.4f}")
    print(f"RMSE : {rmse:.4f}")
    print(f"R²   : {r2:.4f}")

    return resultado


def analizar_picos(nombre, y_real, pred):

    # Umbral fijo para que la comparación sea reproducible.
    # 10 unidades representa una demanda diaria elevada
    # respecto a la media global (~7.56).
    umbral = 10

    mascara = y_real >= umbral

    if mascara.sum() == 0:
        print(
            f"\n{nombre}: no hay registros "
            f"con demanda >= {umbral}."
        )
        return

    mae_picos = mean_absolute_error(
        y_real[mascara],
        pred[mascara],
    )

    rmse_picos = np.sqrt(
        mean_squared_error(
            y_real[mascara],
            pred[mascara],
        )
    )

    print()
    print(f"Análisis de picos: {nombre}")
    print(f"Registros con demanda >= {umbral}: {mascara.sum()}")
    print(f"MAE picos : {mae_picos:.4f}")
    print(f"RMSE picos: {rmse_picos:.4f}")


def main():

    print("=" * 70)
    print("EXPERIMENTO: TRANSFORMACIÓN LOGARÍTMICA DE LA DEMANDA")
    print("=" * 70)

    df = pd.read_csv(DATASET_PATH)

    df["fecha"] = pd.to_datetime(
        df["fecha"]
    )

    # Orden temporal oficial
    df = df.sort_values(
        ["fecha", "producto_id"]
    ).reset_index(drop=True)

    indice_test = int(
        len(df) * 0.80
    )

    desarrollo = df.iloc[
        :indice_test
    ].copy()

    test = df.iloc[
        indice_test:
    ].copy()

    X_train = desarrollo[FEATURES]
    X_test = test[FEATURES]

    y_train = desarrollo[TARGET]
    y_test = test[TARGET]

    print()
    print(f"Dataset: {len(df)}")
    print(f"Desarrollo: {len(desarrollo)}")
    print(f"Test: {len(test)}")
    print(
        f"Test: {test['fecha'].min().date()} "
        f"→ {test['fecha'].max().date()}"
    )

    # --------------------------------------------------
    # MODELO BASE
    # --------------------------------------------------

    base = evaluar_modelo(
        "MODELO BASE - DEMANDA NORMAL",
        X_train,
        y_train,
        X_test,
        y_test,
        log_target=False,
    )

    # --------------------------------------------------
    # MODELO LOG
    # --------------------------------------------------

    log_modelo = evaluar_modelo(
        "MODELO LOG1P - DEMANDA TRANSFORMADA",
        X_train,
        y_train,
        X_test,
        y_test,
        log_target=True,
    )

    # --------------------------------------------------
    # COMPARACIÓN
    # --------------------------------------------------

    mejora_mae = (
        (base["mae"] - log_modelo["mae"])
        / base["mae"]
    ) * 100

    mejora_rmse = (
        (base["rmse"] - log_modelo["rmse"])
        / base["rmse"]
    ) * 100

    cambio_r2 = (
        log_modelo["r2"] -
        base["r2"]
    )

    print()
    print("=" * 70)
    print("COMPARACIÓN")
    print("=" * 70)

    print(
        f"Base MAE : {base['mae']:.4f}"
    )

    print(
        f"Log1p MAE: {log_modelo['mae']:.4f}"
    )

    print(
        f"Base RMSE : {base['rmse']:.4f}"
    )

    print(
        f"Log1p RMSE: {log_modelo['rmse']:.4f}"
    )

    print(
        f"Base R² : {base['r2']:.4f}"
    )

    print(
        f"Log1p R²: {log_modelo['r2']:.4f}"
    )

    print()
    print(
        f"Mejora MAE : {mejora_mae:+.2f}%"
    )

    print(
        f"Mejora RMSE: {mejora_rmse:+.2f}%"
    )

    print(
        f"Cambio R²  : {cambio_r2:+.4f}"
    )

    # --------------------------------------------------
    # PICOS
    # --------------------------------------------------

    analizar_picos(
        "MODELO BASE",
        y_test.to_numpy(),
        base["predicciones"],
    )

    analizar_picos(
        "MODELO LOG1P",
        y_test.to_numpy(),
        log_modelo["predicciones"],
    )

    # --------------------------------------------------
    # ERROR POR RANGO DE DEMANDA
    # --------------------------------------------------

    y_real = y_test.to_numpy()

    rangos = [
        ("Baja (0-4)", y_real <= 4),
        ("Media (5-9)", (y_real >= 5) & (y_real <= 9)),
        ("Alta (10-19)", (y_real >= 10) & (y_real <= 19)),
        ("Muy alta (20+)", y_real >= 20),
    ]

    print()
    print("=" * 70)
    print("ERROR POR NIVEL DE DEMANDA")
    print("=" * 70)

    for nombre, mascara in rangos:

        if mascara.sum() == 0:
            continue

        mae_base = mean_absolute_error(
            y_real[mascara],
            base["predicciones"][mascara],
        )

        mae_log = mean_absolute_error(
            y_real[mascara],
            log_modelo["predicciones"][mascara],
        )

        print()
        print(nombre)
        print(f"Registros: {mascara.sum()}")
        print(f"MAE Base: {mae_base:.4f}")
        print(f"MAE Log1p: {mae_log:.4f}")

    print()
    print("=" * 70)

    if log_modelo["mae"] < base["mae"]:
        print(
            "✅ LOG1P mejora el MAE global."
        )
    else:
        print(
            "❌ LOG1P NO mejora el MAE global."
        )

    print()
    print(
        "modelo_demanda.pkl NO fue modificado."
    )


if __name__ == "__main__":
    main()