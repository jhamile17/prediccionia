import pandas as pd
import numpy as np

from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import (
    mean_absolute_error,
    mean_squared_error,
    r2_score,
)


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


CONFIGURACIONES = [
    {
        "nombre": "Continuo alpha=0.02 max=2.0",
        "alpha": 0.02,
        "max_peso": 2.0,
    },
    {
        "nombre": "Continuo alpha=0.03 max=2.5",
        "alpha": 0.03,
        "max_peso": 2.5,
    },
    {
        "nombre": "Continuo alpha=0.04 max=3.0",
        "alpha": 0.04,
        "max_peso": 3.0,
    },
    {
        "nombre": "Continuo alpha=0.05 max=3.0",
        "alpha": 0.05,
        "max_peso": 3.0,
    },
    {
        "nombre": "Continuo alpha=0.06 max=3.5",
        "alpha": 0.06,
        "max_peso": 3.5,
    },
    {
        "nombre": "Continuo alpha=0.08 max=4.0",
        "alpha": 0.08,
        "max_peso": 4.0,
    },
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


def crear_pesos(y, alpha, max_peso):

    y = np.asarray(y, dtype=float)

    exceso = np.maximum(
        y - 10,
        0,
    )

    pesos = (
        1.0
        + alpha * exceso
    )

    pesos = np.minimum(
        pesos,
        max_peso,
    )

    return pesos


def evaluar(configuracion, desarrollo, test):

    modelo = crear_modelo()

    pesos = crear_pesos(
        desarrollo["demanda"],
        configuracion["alpha"],
        configuracion["max_peso"],
    )

    modelo.fit(
        desarrollo[FEATURES],
        desarrollo["demanda"],
        sample_weight=pesos,
    )

    pred = modelo.predict(
        test[FEATURES]
    )

    pred = np.maximum(
        pred,
        0,
    )

    y_real = test["demanda"].to_numpy()

    mae = mean_absolute_error(
        y_real,
        pred,
    )

    rmse = np.sqrt(
        mean_squared_error(
            y_real,
            pred,
        )
    )

    r2 = r2_score(
        y_real,
        pred,
    )

    mascara_picos = (
        y_real >= 20
    )

    if mascara_picos.sum() > 0:

        mae_picos = mean_absolute_error(
            y_real[mascara_picos],
            pred[mascara_picos],
        )

        rmse_picos = np.sqrt(
            mean_squared_error(
                y_real[mascara_picos],
                pred[mascara_picos],
            )
        )

    else:

        mae_picos = np.nan
        rmse_picos = np.nan

    return {
        "configuracion": configuracion["nombre"],
        "mae": mae,
        "rmse": rmse,
        "r2": r2,
        "mae_picos": mae_picos,
        "rmse_picos": rmse_picos,
    }


def main():

    print("=" * 70)
    print("EXPERIMENTO: PESOS CONTINUOS SEGÚN DEMANDA")
    print("=" * 70)

    df = pd.read_csv(
        DATASET_PATH
    )

    df["fecha"] = pd.to_datetime(
        df["fecha"]
    )

    df = df.sort_values(
        [
            "fecha",
            "producto_id",
        ]
    ).reset_index(
        drop=True
    )

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
    print(
        f"Dataset: {len(df)}"
    )

    print(
        f"Desarrollo: {len(desarrollo)}"
    )

    print(
        f"Test: {len(test)}"
    )

    print(
        f"Test: "
        f"{test['fecha'].min().date()} "
        f"→ "
        f"{test['fecha'].max().date()}"
    )

    picos = (
        test["demanda"] >= 20
    ).sum()

    print(
        f"Picos >=20: {picos}"
    )

    if picos != 107:

        print(
            "ADVERTENCIA: el test no coincide "
            "con nuestra referencia de 107 picos."
        )

    resultados = []

    for i, configuracion in enumerate(
        CONFIGURACIONES,
        start=1,
    ):

        print()
        print(
            f"[{i}/{len(CONFIGURACIONES)}] "
            f"{configuracion['nombre']}"
        )

        print(
            f"alpha={configuracion['alpha']} | "
            f"max_peso={configuracion['max_peso']}"
        )

        resultado = evaluar(
            configuracion,
            desarrollo,
            test,
        )

        resultados.append(
            resultado
        )

        print(
            f"MAE={resultado['mae']:.4f} | "
            f"RMSE={resultado['rmse']:.4f} | "
            f"R²={resultado['r2']:.4f}"
        )

        print(
            f"MAE picos={resultado['mae_picos']:.4f} | "
            f"RMSE picos={resultado['rmse_picos']:.4f}"
        )

    resultados_df = pd.DataFrame(
        resultados
    ).sort_values(
        "mae"
    )

    print()
    print("=" * 70)
    print("RANKING FINAL")
    print("=" * 70)

    print(
        resultados_df.to_string(
            index=False
        )
    )

    mejor_global = (
        resultados_df
        .iloc[0]
    )

    mejor_picos = (
        resultados_df
        .sort_values(
            "mae_picos"
        )
        .iloc[0]
    )

    # Referencia
    rf_mae = 4.252990156763385
    rf_rmse = 5.822283612757488
    rf_r2 = 0.24703916152859473
    rf_mae_picos = 13.585647

    print()
    print("=" * 70)
    print("MEJOR CONFIGURACIÓN GLOBAL")
    print("=" * 70)

    print(
        mejor_global.to_string()
    )

    print()
    print("=" * 70)
    print("MEJOR CONFIGURACIÓN PARA PICOS")
    print("=" * 70)

    print(
        mejor_picos.to_string()
    )

    mejora_mae = (
        (
            rf_mae
            - mejor_global["mae"]
        )
        / rf_mae
    ) * 100

    mejora_rmse = (
        (
            rf_rmse
            - mejor_global["rmse"]
        )
        / rf_rmse
    ) * 100

    cambio_r2 = (
        mejor_global["r2"]
        - rf_r2
    )

    mejora_picos = (
        (
            rf_mae_picos
            - mejor_global["mae_picos"]
        )
        / rf_mae_picos
    ) * 100

    print()
    print("=" * 70)
    print("COMPARACIÓN CONTRA RANDOM FOREST ACTUAL")
    print("=" * 70)

    print(
        f"RF MAE: {rf_mae:.4f}"
    )

    print(
        f"Pesos continuos MAE: "
        f"{mejor_global['mae']:.4f}"
    )

    print(
        f"Mejora MAE: "
        f"{mejora_mae:+.2f}%"
    )

    print()

    print(
        f"RF RMSE: {rf_rmse:.4f}"
    )

    print(
        f"Pesos continuos RMSE: "
        f"{mejor_global['rmse']:.4f}"
    )

    print(
        f"Mejora RMSE: "
        f"{mejora_rmse:+.2f}%"
    )

    print()

    print(
        f"RF R²: {rf_r2:.4f}"
    )

    print(
        f"Pesos continuos R²: "
        f"{mejor_global['r2']:.4f}"
    )

    print(
        f"Cambio R²: "
        f"{cambio_r2:+.4f}"
    )

    print()

    print(
        f"RF MAE picos: "
        f"{rf_mae_picos:.4f}"
    )

    print(
        f"Pesos continuos MAE picos: "
        f"{mejor_global['mae_picos']:.4f}"
    )

    print(
        f"Mejora picos: "
        f"{mejora_picos:+.2f}%"
    )

    print()
    print("=" * 70)

    if mejor_global["mae"] < rf_mae:

        print(
            "✅ Los pesos continuos mejoran "
            "el MAE global."
        )

    else:

        print(
            "❌ Los pesos continuos no mejoran "
            "el MAE global."
        )

    print()

    if mejor_global["mae_picos"] < rf_mae_picos:

        print(
            "✅ Los pesos continuos mejoran "
            "los picos."
        )

    else:

        print(
            "⚠️ Los pesos continuos no mejoran "
            "los picos."
        )

    print()
    print(
        "modelo_demanda.pkl NO fue modificado."
    )


if __name__ == "__main__":
    main()