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
    "demanda_28_dias",
]

TARGET = "demanda"


def crear_demanda_28(df):

    df = df.sort_values(
        ["producto_id", "fecha"]
    ).copy()

    df["demanda_28_dias"] = (
        df.groupby("producto_id")["demanda"]
        .shift(28)
        .fillna(0)
    )

    return df


def evaluar(nombre, features, train, test):

    modelo = RandomForestRegressor(
        n_estimators=300,
        max_depth=20,
        min_samples_leaf=2,
        max_features=1.0,
        random_state=42,
        n_jobs=-1,
    )

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
    print("EXPERIMENTO: DEMANDA A 28 DÍAS")
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
    df = crear_demanda_28(df)

    print(
        "\nNulos en demanda_28_dias:",
        df["demanda_28_dias"].isna().sum()
    )

    # División temporal exactamente como la línea base
    indice_test = int(len(df) * 0.80)

    desarrollo = df.iloc[:indice_test].copy()
    test = df.iloc[indice_test:].copy()

    # Para comparar correctamente, mantendremos
    # el mismo período de test.
    resultados = []

    # --------------------------------------------------
    # MODELO BASE
    # --------------------------------------------------

    resultados.append(
        evaluar(
            "MODELO BASE - 12 VARIABLES",
            FEATURES_BASE,
            desarrollo,
            test,
        )
    )

    # --------------------------------------------------
    # MODELO MEJORADO
    # --------------------------------------------------

    resultados.append(
        evaluar(
            "MODELO MEJORADO - + DEMANDA 28 DÍAS",
            FEATURES_MEJORADAS,
            desarrollo,
            test,
        )
    )

    # --------------------------------------------------
    # COMPARACIÓN
    # --------------------------------------------------

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

    mejora_r2 = (
        mejorado["r2"] - base["r2"]
    )

    print()
    print(
        f"Mejora MAE : {mejora_mae:.2f}%"
    )

    print(
        f"Mejora RMSE: {mejora_rmse:.2f}%"
    )

    print(
        f"Cambio R²  : {mejora_r2:+.4f}"
    )

    print()

    if mejorado["mae"] < base["mae"]:
        print(
            "✅ demanda_28_dias MEJORA el MAE."
        )
    else:
        print(
            "❌ demanda_28_dias NO mejora el MAE."
        )

    print()
    print("El modelo actual NO fue modificado.")


if __name__ == "__main__":
    main()