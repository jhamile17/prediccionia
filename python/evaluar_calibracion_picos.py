import pandas as pd
import numpy as np

from sklearn.ensemble import (
    RandomForestClassifier,
    RandomForestRegressor,
)

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


UMBRAL_PICO = 10


def crear_regresor():

    return RandomForestRegressor(
        n_estimators=400,
        max_depth=20,
        min_samples_leaf=2,
        max_features="sqrt",
        random_state=42,
        n_jobs=-1,
    )


def crear_clasificador():

    return RandomForestClassifier(
        n_estimators=400,
        max_depth=20,
        min_samples_leaf=2,
        max_features="sqrt",
        class_weight="balanced",
        random_state=42,
        n_jobs=-1,
    )


def main():

    print("=" * 70)
    print("CALIBRACIÓN DE PREDICCIÓN SEGÚN PROBABILIDAD DE PICO")
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

    # ==================================================
    # MODELO BASE
    # ==================================================

    modelo = crear_regresor()

    modelo.fit(
        desarrollo[FEATURES],
        desarrollo["demanda"],
    )

    pred_base = modelo.predict(
        test[FEATURES]
    )

    pred_base = np.maximum(
        pred_base,
        0,
    )

    mae_base = mean_absolute_error(
        test["demanda"],
        pred_base,
    )

    rmse_base = np.sqrt(
        mean_squared_error(
            test["demanda"],
            pred_base,
        )
    )

    print()
    print("=" * 70)
    print("MODELO BASE")
    print("=" * 70)

    print(
        f"MAE : {mae_base:.4f}"
    )

    print(
        f"RMSE: {rmse_base:.4f}"
    )

    # ==================================================
    # DETECTOR
    # ==================================================

    desarrollo = desarrollo.copy()
    test = test.copy()

    desarrollo["es_pico"] = (
        desarrollo["demanda"]
        >= UMBRAL_PICO
    ).astype(int)

    test["es_pico"] = (
        test["demanda"]
        >= UMBRAL_PICO
    ).astype(int)

    clasificador = crear_clasificador()

    clasificador.fit(
        desarrollo[FEATURES],
        desarrollo["es_pico"],
    )

    prob_pico = clasificador.predict_proba(
        test[FEATURES]
    )[:, 1]

    test["prob_pico"] = prob_pico

    test["pred_base"] = pred_base

    # ==================================================
    # ANÁLISIS POR RANGOS
    # ==================================================

    bins = [
        0.0,
        0.2,
        0.4,
        0.6,
        0.8,
        1.01,
    ]

    labels = [
        "0%-20%",
        "20%-40%",
        "40%-60%",
        "60%-80%",
        "80%-100%",
    ]

    test["rango_probabilidad"] = pd.cut(
        test["prob_pico"],
        bins=bins,
        labels=labels,
        include_lowest=True,
    )

    grupos = []

    for rango, grupo in test.groupby(
        "rango_probabilidad",
        observed=False,
    ):

        if len(grupo) == 0:
            continue

        demanda_media = (
            grupo["demanda"]
            .mean()
        )

        pred_media = (
            grupo["pred_base"]
            .mean()
        )

        error = (
            demanda_media
            - pred_media
        )

        porcentaje_pico = (
            grupo["es_pico"]
            .mean()
            * 100
        )

        mae = mean_absolute_error(
            grupo["demanda"],
            grupo["pred_base"],
        )

        grupos.append({
            "rango": str(rango),
            "registros": len(grupo),
            "prob_media": grupo["prob_pico"].mean(),
            "pct_real_pico": porcentaje_pico,
            "demanda_media": demanda_media,
            "pred_media": pred_media,
            "sesgo": error,
            "mae": mae,
        })

    resumen = pd.DataFrame(
        grupos
    )

    print()
    print("=" * 70)
    print("CALIBRACIÓN")
    print("=" * 70)

    print(
        resumen.to_string(
            index=False
        )
    )

    # ==================================================
    # CORRECCIONES CANDIDATAS
    # ==================================================

    print()
    print("=" * 70)
    print("PRUEBA DE CORRECCIONES")
    print("=" * 70)

    factores = [
        1.00,
        1.05,
        1.10,
        1.15,
        1.20,
        1.25,
        1.30,
    ]

    resultados = []

    for factor in factores:

        # Aplicamos la corrección solamente
        # cuando la probabilidad de pico es >= 50%.
        pred = pred_base.copy()

        mascara = (
            prob_pico >= 0.50
        )

        pred[mascara] = (
            pred[mascara]
            * factor
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

        mascara_20 = (
            test["demanda"].to_numpy()
            >= 20
        )

        if mascara_20.sum() > 0:

            mae_picos = mean_absolute_error(
                test.loc[
                    mascara_20,
                    "demanda",
                ],
                pred[
                    mascara_20
                ],
            )

        else:

            mae_picos = np.nan

        resultados.append({
            "factor": factor,
            "mae": mae,
            "rmse": rmse,
            "r2": r2,
            "mae_picos": mae_picos,
        })

    resultados_df = pd.DataFrame(
        resultados
    )

    print(
        resultados_df.to_string(
            index=False
        )
    )

    mejor_global = (
        resultados_df
        .sort_values("mae")
        .iloc[0]
    )

    mejor_picos = (
        resultados_df
        .sort_values("mae_picos")
        .iloc[0]
    )

    print()
    print("=" * 70)
    print("MEJOR FACTOR GLOBAL")
    print("=" * 70)

    print(
        mejor_global.to_string()
    )

    print()
    print("=" * 70)
    print("MEJOR FACTOR PARA PICOS")
    print("=" * 70)

    print(
        mejor_picos.to_string()
    )

    print()
    print("=" * 70)
    print("REFERENCIA")
    print("=" * 70)

    print(
        f"Base MAE: {mae_base:.4f}"
    )

    print(
        "modelo_demanda.pkl NO fue modificado."
    )


if __name__ == "__main__":
    main()