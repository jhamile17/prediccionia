import pandas as pd
import numpy as np

from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import (
    mean_absolute_error,
    mean_squared_error,
    r2_score,
)


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
    "promedio_ponderado_4_semanas",
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


def crear_variable(df):

    df = df.sort_values(
        ["producto_id", "fecha"]
    ).copy()

    grupo = df.groupby(
        "producto_id"
    )["demanda"]

    d7 = grupo.shift(7)
    d14 = grupo.shift(14)
    d21 = grupo.shift(21)
    d28 = grupo.shift(28)

    df["promedio_ponderado_4_semanas"] = (
        d7 * 0.40
        + d14 * 0.30
        + d21 * 0.20
        + d28 * 0.10
    )

    df[
        "promedio_ponderado_4_semanas"
    ] = (
        df[
            "promedio_ponderado_4_semanas"
        ]
        .fillna(0)
    )

    return df


def evaluar(
    nombre,
    features,
    desarrollo,
    test,
):

    modelo = crear_modelo()

    modelo.fit(
        desarrollo[features],
        desarrollo["demanda"],
    )

    pred = modelo.predict(
        test[features]
    )

    pred = np.maximum(
        pred,
        0,
    )

    mae = mean_absolute_error(
        test["demanda"],
        pred,
    )

    rmse = np.sqrt(
        mean_squared_error(
            test["demanda"],
            pred,
        )
    )

    r2 = r2_score(
        test["demanda"],
        pred,
    )

    return {
        "modelo": nombre,
        "mae": mae,
        "rmse": rmse,
        "r2": r2,
        "pred": pred,
    }


def analizar_picos(
    nombre,
    test,
    pred,
):

    mascara = (
        test["demanda"]
        .to_numpy()
        >= 20
    )

    if mascara.sum() == 0:
        return

    mae = mean_absolute_error(
        test.loc[
            mascara,
            "demanda"
        ],
        pred[mascara],
    )

    rmse = np.sqrt(
        mean_squared_error(
            test.loc[
                mascara,
                "demanda"
            ],
            pred[mascara],
        )
    )

    print()
    print(
        f"{nombre} - PICOS >=20"
    )

    print(
        f"Registros: "
        f"{mascara.sum()}"
    )

    print(
        f"MAE : {mae:.4f}"
    )

    print(
        f"RMSE: {rmse:.4f}"
    )


def main():

    print("=" * 70)
    print(
        "EXPERIMENTO: MEDIA PONDERADA DE 4 SEMANAS"
    )
    print("=" * 70)

    df = pd.read_csv(
        DATASET_PATH
    )

    df["fecha"] = pd.to_datetime(
        df["fecha"]
    )

    # Orden temporal oficial
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
        f"Desarrollo: "
        f"{len(desarrollo)}"
    )

    print(
        f"Test: "
        f"{len(test)}"
    )

    print(
        f"Test: "
        f"{test['fecha'].min().date()} "
        f"→ "
        f"{test['fecha'].max().date()}"
    )

    # Crear variable
    df = crear_variable(
        df
    )

    print()
    print(
        "Nulos en nueva variable:",
        df[
            "promedio_ponderado_4_semanas"
        ].isna().sum()
    )

    print(
        "Promedio:",
        round(
            df[
                "promedio_ponderado_4_semanas"
            ].mean(),
            4,
        )
    )

    print(
        "Mínimo:",
        round(
            df[
                "promedio_ponderado_4_semanas"
            ].min(),
            4,
        )
    )

    print(
        "Máximo:",
        round(
            df[
                "promedio_ponderado_4_semanas"
            ].max(),
            4,
        )
    )

    # Volver a separar después de crear variable
    desarrollo = df.iloc[
        :indice_test
    ].copy()

    test = df.iloc[
        indice_test:
    ].copy()

    # Modelo base
    base = evaluar(
        "MODELO BASE - 12 VARIABLES",
        FEATURES_BASE,
        desarrollo,
        test,
    )

    # Modelo mejorado
    mejorado = evaluar(
        "MODELO MEJORADO - + MEDIA PONDERADA 4 SEMANAS",
        FEATURES_MEJORADAS,
        desarrollo,
        test,
    )

    # Resultados
    print()
    print("=" * 70)
    print("RESULTADOS")
    print("=" * 70)

    print()
    print(
        base["modelo"]
    )

    print(
        f"MAE : {base['mae']:.4f}"
    )

    print(
        f"RMSE: {base['rmse']:.4f}"
    )

    print(
        f"R²  : {base['r2']:.4f}"
    )

    print()
    print(
        mejorado["modelo"]
    )

    print(
        f"MAE : {mejorado['mae']:.4f}"
    )

    print(
        f"RMSE: {mejorado['rmse']:.4f}"
    )

    print(
        f"R²  : {mejorado['r2']:.4f}"
    )

    mejora_mae = (
        (
            base["mae"]
            - mejorado["mae"]
        )
        / base["mae"]
    ) * 100

    mejora_rmse = (
        (
            base["rmse"]
            - mejorado["rmse"]
        )
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
        f"Mejora MAE : "
        f"{mejora_mae:+.2f}%"
    )

    print(
        f"Mejora RMSE: "
        f"{mejora_rmse:+.2f}%"
    )

    print(
        f"Cambio R²  : "
        f"{cambio_r2:+.4f}"
    )

    # Picos
    analizar_picos(
        "MODELO BASE",
        test,
        base["pred"],
    )

    analizar_picos(
        "MODELO MEJORADO",
        test,
        mejorado["pred"],
    )

    print()
    print("=" * 70)

    if mejorado["mae"] < base["mae"]:
        print(
            "✅ La media ponderada "
            "MEJORA el MAE."
        )
    else:
        print(
            "❌ La media ponderada "
            "NO mejora el MAE."
        )

    print()
    print(
        "modelo_demanda.pkl NO fue modificado."
    )


if __name__ == "__main__":
    main()