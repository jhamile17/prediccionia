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
    "promedio_mismo_dia",
]


def crear_promedio_mismo_dia(df):
    """
    Calcula, para cada producto y día de semana,
    el promedio histórico disponible antes de la fecha actual.

    Importante:
    usamos shift(1) antes del expanding mean para evitar
    utilizar la demanda del propio día objetivo.
    """

    df = df.sort_values(
        ["producto_id", "fecha"]
    ).copy()

    historico = (
        df.groupby(
            ["producto_id", "dia_semana"]
        )["demanda"]
        .transform(
            lambda s: s.shift(1).expanding().mean()
        )
    )

    df["promedio_mismo_dia"] = (
        historico.fillna(0)
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
        train["demanda"]
    )

    pred = modelo.predict(
        test[features]
    )

    pred = np.maximum(pred, 0)

    mae = mean_absolute_error(
        test["demanda"],
        pred
    )

    rmse = np.sqrt(
        mean_squared_error(
            test["demanda"],
            pred
        )
    )

    r2 = r2_score(
        test["demanda"],
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
    print("EXPERIMENTO: PROMEDIO MISMO PRODUCTO + DÍA")
    print("=" * 60)

    df = pd.read_csv(DATASET_PATH)

    df["fecha"] = pd.to_datetime(
        df["fecha"]
    )

    # Orden temporal oficial
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

    # --------------------------------------------------
    # CORTE TEMPORAL OFICIAL
    # --------------------------------------------------

    indice_test = int(len(df) * 0.80)

    desarrollo = df.iloc[:indice_test].copy()
    test = df.iloc[indice_test:].copy()

    print(
        f"\nDesarrollo: {len(desarrollo)}"
    )

    print(
        f"Test: {len(test)}"
    )

    print(
        f"Período test: "
        f"{test['fecha'].min().date()} "
        f"→ {test['fecha'].max().date()}"
    )

    # --------------------------------------------------
    # CREAR VARIABLE SIN USAR EL FUTURO
    # --------------------------------------------------

    df = crear_promedio_mismo_dia(df)

    print(
        "\nNulos en promedio_mismo_dia:",
        df["promedio_mismo_dia"].isna().sum()
    )

    print(
        "Promedio general:",
        round(
            df["promedio_mismo_dia"].mean(),
            4
        )
    )

    # Volver a separar después de crear variable
    desarrollo = df.iloc[:indice_test].copy()
    test = df.iloc[indice_test:].copy()

    resultados = []

    # --------------------------------------------------
    # BASE
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
    # MEJORADO
    # --------------------------------------------------

    resultados.append(
        evaluar(
            "MODELO MEJORADO - + PROMEDIO MISMO DÍA",
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
        comparacion.to_string(
            index=False
        )
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
        mejorado["r2"] -
        base["r2"]
    )

    print()
    print(
        f"Mejora MAE : {mejora_mae:.2f}%"
    )

    print(
        f"Mejora RMSE: {mejora_rmse:.2f}%"
    )

    print(
        f"Cambio R²  : {cambio_r2:+.4f}"
    )

    print()

    if mejorado["mae"] < base["mae"]:
        print(
            "✅ promedio_mismo_dia MEJORA el MAE."
        )
    else:
        print(
            "❌ promedio_mismo_dia NO mejora el MAE."
        )

    print()
    print(
        "El modelo actual NO fue modificado."
    )


if __name__ == "__main__":
    main()