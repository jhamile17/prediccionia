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

FEATURES_MEJORADAS = FEATURES_BASE + [
    "promedio_4_semanas",
]

TARGET = "demanda"


def crear_promedio_4_semanas(df):
    df = df.sort_values(
        ["producto_id", "fecha"]
    ).copy()

    grupo = df.groupby("producto_id")["demanda"]

    demanda_7 = grupo.shift(7)
    demanda_14 = grupo.shift(14)
    demanda_21 = grupo.shift(21)
    demanda_28 = grupo.shift(28)

    df["promedio_4_semanas"] = (
        pd.concat(
            [
                demanda_7,
                demanda_14,
                demanda_21,
                demanda_28,
            ],
            axis=1
        )
        .mean(axis=1)
        .fillna(0)
    )

    return df


def crear_modelo():
    return RandomForestRegressor(
        n_estimators=300,
        max_depth=20,
        min_samples_leaf=2,
        max_features=1.0,
        random_state=42,
        n_jobs=-1,
    )


def evaluar(nombre, features, train, test):

    modelo = crear_modelo()

    modelo.fit(
        train[features],
        train[TARGET]
    )

    pred = modelo.predict(
        test[features]
    )

    pred = np.maximum(pred, 0)

    mae = mean_absolute_error(
        test[TARGET],
        pred
    )

    rmse = np.sqrt(
        mean_squared_error(
            test[TARGET],
            pred
        )
    )

    r2 = r2_score(
        test[TARGET],
        pred
    )

    print()
    print("=" * 60)
    print(nombre)
    print("=" * 60)

    print(f"MAE  : {mae:.4f}")
    print(f"RMSE : {rmse:.4f}")
    print(f"R²   : {r2:.4f}")

    return {
        "modelo": nombre,
        "mae": mae,
        "rmse": rmse,
        "r2": r2,
    }


def main():

    print("=" * 60)
    print("EXPERIMENTO: PROMEDIO DE 4 SEMANAS")
    print("=" * 60)

    df = pd.read_csv(DATASET_PATH)

    df["fecha"] = pd.to_datetime(
        df["fecha"]
    )

    df = df.sort_values(
        ["fecha", "producto_id"]
    ).reset_index(drop=True)

    print(
        f"\nDataset: {len(df)} registros"
    )

    print(
        f"Fecha: {df['fecha'].min().date()} "
        f"→ {df['fecha'].max().date()}"
    )

    # Crear nueva variable
    df = crear_promedio_4_semanas(df)

    print(
        "\nNulos en promedio_4_semanas:",
        df["promedio_4_semanas"].isna().sum()
    )

    print(
        "Promedio de la nueva variable:",
        round(df["promedio_4_semanas"].mean(), 4)
    )

    # División temporal
    indice_test = int(len(df) * 0.80)

    desarrollo = df.iloc[:indice_test].copy()
    test = df.iloc[indice_test:].copy()

    print(
        f"\nDesarrollo: {len(desarrollo)} registros"
    )

    print(
        f"Test:       {len(test)} registros"
    )

    print(
        f"Test: {test['fecha'].min().date()} "
        f"→ {test['fecha'].max().date()}"
    )

    resultados = []

    # Modelo base
    resultados.append(
        evaluar(
            "MODELO BASE - 12 VARIABLES",
            FEATURES_BASE,
            desarrollo,
            test,
        )
    )

    # Modelo mejorado
    resultados.append(
        evaluar(
            "MODELO MEJORADO - + PROMEDIO 4 SEMANAS",
            FEATURES_MEJORADAS,
            desarrollo,
            test,
        )
    )

    # Comparación
    comparacion = pd.DataFrame(
        resultados
    )

    print()
    print("=" * 60)
    print("COMPARACIÓN FINAL")
    print("=" * 60)

    print(
        comparacion.to_string(index=False)
    )

    base = comparacion.iloc[0]
    mejorado = comparacion.iloc[1]

    mejora_mae = (
        (base["mae"] - mejorado["mae"])
        / base["mae"]
    ) * 100

    mejora_rmse = (
        (base["rmse"] - mejorado["rmse"])
        / base["rmse"]
    ) * 100

    cambio_r2 = (
        mejorado["r2"] - base["r2"]
    )

    print()
    print(f"Mejora MAE : {mejora_mae:.2f}%")
    print(f"Mejora RMSE: {mejora_rmse:.2f}%")
    print(f"Cambio R²  : {cambio_r2:+.4f}")

    print()

    if mejorado["mae"] < base["mae"]:
        print(
            "✅ promedio_4_semanas MEJORA el MAE."
        )
    else:
        print(
            "❌ promedio_4_semanas NO mejora el MAE."
        )

    print()
    print("El modelo actual NO fue modificado.")


if __name__ == "__main__":
    main()