import pandas as pd
import numpy as np

from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score


DATASET_PATH = "storage/app/datasets/dataset_demanda.csv"


FEATURES_BASE = [
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


def crear_modelo():

    return RandomForestRegressor(
        n_estimators=400,
        max_depth=20,
        min_samples_leaf=2,
        max_features="sqrt",
        random_state=42,
        n_jobs=-1,
    )


def evaluar(nombre, X_train, y_train, X_test, y_test):

    modelo = crear_modelo()

    modelo.fit(
        X_train,
        y_train,
    )

    pred = modelo.predict(
        X_test
    )

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

    print()
    print("=" * 70)
    print(nombre)
    print("=" * 70)

    print(f"MAE : {mae:.4f}")
    print(f"RMSE: {rmse:.4f}")
    print(f"R²  : {r2:.4f}")

    return {
        "nombre": nombre,
        "mae": mae,
        "rmse": rmse,
        "r2": r2,
        "pred": pred,
    }


def analizar_picos(nombre, y_real, pred):

    mascara = y_real >= 20

    if mascara.sum() == 0:
        return

    mae = mean_absolute_error(
        y_real[mascara],
        pred[mascara],
    )

    rmse = np.sqrt(
        mean_squared_error(
            y_real[mascara],
            pred[mascara],
        )
    )

    print()
    print(f"{nombre} - PICOS >=20")
    print(f"Registros: {mascara.sum()}")
    print(f"MAE : {mae:.4f}")
    print(f"RMSE: {rmse:.4f}")


def main():

    print("=" * 70)
    print("EXPERIMENTO: PRODUCTO + DÍA CON ONE-HOT")
    print("=" * 70)

    df = pd.read_csv(
        DATASET_PATH
    )

    df["fecha"] = pd.to_datetime(
        df["fecha"]
    )

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

    base = evaluar(
        "MODELO BASE - 12 VARIABLES",
        desarrollo[FEATURES_BASE],
        desarrollo["demanda"],
        test[FEATURES_BASE],
        test["demanda"],
    )

    # --------------------------------------------------
    # ONE-HOT PRODUCTO + DÍA
    # --------------------------------------------------

    combinado = pd.concat(
        [
            desarrollo,
            test,
        ],
        axis=0,
    )

    combinado["producto_dia"] = (
        combinado["producto_id"].astype(str)
        + "_"
        + combinado["dia_semana"].astype(str)
    )

    dummies = pd.get_dummies(
        combinado["producto_dia"],
        prefix="producto_dia",
        dtype=int,
    )

    combinado = pd.concat(
        [
            combinado,
            dummies,
        ],
        axis=1,
    )

    columnas_onehot = list(
        dummies.columns
    )

    desarrollo_onehot = combinado.iloc[
        :len(desarrollo)
    ].copy()

    test_onehot = combinado.iloc[
        len(desarrollo):
    ].copy()

    features_onehot = (
        FEATURES_BASE
        + columnas_onehot
    )

    print()
    print(
        "Variables base:",
        len(FEATURES_BASE)
    )

    print(
        "Variables producto-día:",
        len(columnas_onehot)
    )

    print(
        "Variables totales:",
        len(features_onehot)
    )

    mejorado = evaluar(
        "MODELO MEJORADO - PRODUCTO_DÍA ONE-HOT",
        desarrollo_onehot[features_onehot],
        desarrollo_onehot["demanda"],
        test_onehot[features_onehot],
        test_onehot["demanda"],
    )

    # --------------------------------------------------
    # COMPARACIÓN
    # --------------------------------------------------

    mejora_mae = (
        (base["mae"] - mejorado["mae"])
        / base["mae"]
    ) * 100

    mejora_rmse = (
        (base["rmse"] - mejorado["rmse"])
        / base["rmse"]
    ) * 100

    cambio_r2 = (
        mejorado["r2"]
        - base["r2"]
    )

    print()
    print("=" * 70)
    print("COMPARACIÓN")
    print("=" * 70)

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

    y_real = test["demanda"].to_numpy()

    analizar_picos(
        "MODELO BASE",
        y_real,
        base["pred"],
    )

    analizar_picos(
        "MODELO PRODUCTO_DÍA ONE-HOT",
        y_real,
        mejorado["pred"],
    )

    print()
    print("=" * 70)

    if mejorado["mae"] < base["mae"]:
        print(
            "✅ ONE-HOT mejora el MAE."
        )
    else:
        print(
            "❌ ONE-HOT no mejora el MAE."
        )

    print()
    print(
        "modelo_demanda.pkl NO fue modificado."
    )


if __name__ == "__main__":
    main()