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
    precision_score,
    recall_score,
    f1_score,
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


def crear_clasificador():

    return RandomForestClassifier(
        n_estimators=400,
        max_depth=20,
        min_samples_leaf=2,
        max_features="sqrt",
        random_state=42,
        n_jobs=-1,
        class_weight="balanced",
    )


def crear_regresor():

    return RandomForestRegressor(
        n_estimators=400,
        max_depth=20,
        min_samples_leaf=2,
        max_features="sqrt",
        random_state=42,
        n_jobs=-1,
    )


def evaluar_baseline(desarrollo, test):

    modelo = crear_regresor()

    modelo.fit(
        desarrollo[FEATURES],
        desarrollo["demanda"],
    )

    pred = modelo.predict(
        test[FEATURES]
    )

    pred = np.maximum(
        pred,
        0,
    )

    return pred


def main():

    print("=" * 70)
    print("EXPERIMENTO: MODELO EN DOS ETAPAS")
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

    # ==================================================
    # MODELO BASE
    # ==================================================

    pred_base = evaluar_baseline(
        desarrollo,
        test,
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

    r2_base = r2_score(
        test["demanda"],
        pred_base,
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

    print(
        f"R²  : {r2_base:.4f}"
    )

    # ==================================================
    # ETAPA 1
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

    print()
    print("=" * 70)
    print("ETAPA 1 - DETECTOR DE PICO")
    print("=" * 70)

    print(
        "Umbral:",
        UMBRAL_PICO,
    )

    print(
        "Picos desarrollo:",
        desarrollo["es_pico"].sum(),
    )

    print(
        "Picos test:",
        test["es_pico"].sum(),
    )

    clasificador = crear_clasificador()

    clasificador.fit(
        desarrollo[FEATURES],
        desarrollo["es_pico"],
    )

    pred_clase = clasificador.predict(
        test[FEATURES]
    )

    prob_pico = clasificador.predict_proba(
        test[FEATURES]
    )[:, 1]

    precision = precision_score(
        test["es_pico"],
        pred_clase,
        zero_division=0,
    )

    recall = recall_score(
        test["es_pico"],
        pred_clase,
        zero_division=0,
    )

    f1 = f1_score(
        test["es_pico"],
        pred_clase,
        zero_division=0,
    )

    print(
        f"Precision: {precision:.4f}"
    )

    print(
        f"Recall   : {recall:.4f}"
    )

    print(
        f"F1       : {f1:.4f}"
    )

    # ==================================================
    # ETAPA 2
    # ==================================================

    print()
    print("=" * 70)
    print("ETAPA 2 - REGRESORES")
    print("=" * 70)

    # Regresor para días normales
    desarrollo_normal = desarrollo[
        desarrollo["es_pico"] == 0
    ]

    modelo_normal = crear_regresor()

    modelo_normal.fit(
        desarrollo_normal[FEATURES],
        desarrollo_normal["demanda"],
    )

    # Regresor para días de demanda alta
    desarrollo_pico = desarrollo[
        desarrollo["es_pico"] == 1
    ]

    modelo_pico = crear_regresor()

    modelo_pico.fit(
        desarrollo_pico[FEATURES],
        desarrollo_pico["demanda"],
    )

    print(
        "Registros modelo normal:",
        len(desarrollo_normal),
    )

    print(
        "Registros modelo pico:",
        len(desarrollo_pico),
    )

    # ==================================================
    # PREDICCIÓN FINAL
    # ==================================================

    pred_normal = modelo_normal.predict(
        test[FEATURES]
    )

    pred_pico = modelo_pico.predict(
        test[FEATURES]
    )

    pred_dos_etapas = np.where(
        pred_clase == 1,
        pred_pico,
        pred_normal,
    )

    pred_dos_etapas = np.maximum(
        pred_dos_etapas,
        0,
    )

    mae_dos = mean_absolute_error(
        test["demanda"],
        pred_dos_etapas,
    )

    rmse_dos = np.sqrt(
        mean_squared_error(
            test["demanda"],
            pred_dos_etapas,
        )
    )

    r2_dos = r2_score(
        test["demanda"],
        pred_dos_etapas,
    )

    print()
    print("=" * 70)
    print("MODELO DOS ETAPAS")
    print("=" * 70)

    print(
        f"MAE : {mae_dos:.4f}"
    )

    print(
        f"RMSE: {rmse_dos:.4f}"
    )

    print(
        f"R²  : {r2_dos:.4f}"
    )

    # ==================================================
    # PICOS >= 20
    # ==================================================

    mascara_20 = (
        test["demanda"].to_numpy()
        >= 20
    )

    if mascara_20.sum() > 0:

        mae_base_20 = mean_absolute_error(
            test.loc[
                mascara_20,
                "demanda",
            ],
            pred_base[
                mascara_20
            ],
        )

        mae_dos_20 = mean_absolute_error(
            test.loc[
                mascara_20,
                "demanda",
            ],
            pred_dos_etapas[
                mascara_20
            ],
        )

        rmse_base_20 = np.sqrt(
            mean_squared_error(
                test.loc[
                    mascara_20,
                    "demanda",
                ],
                pred_base[
                    mascara_20
                ],
            )
        )

        rmse_dos_20 = np.sqrt(
            mean_squared_error(
                test.loc[
                    mascara_20,
                    "demanda",
                ],
                pred_dos_etapas[
                    mascara_20
                ],
            )
        )

        print()
        print("=" * 70)
        print("PICOS >=20")
        print("=" * 70)

        print(
            f"Registros: {mascara_20.sum()}"
        )

        print()
        print(
            f"Base MAE : "
            f"{mae_base_20:.4f}"
        )

        print(
            f"Dos etapas MAE: "
            f"{mae_dos_20:.4f}"
        )

        print()

        print(
            f"Base RMSE : "
            f"{rmse_base_20:.4f}"
        )

        print(
            f"Dos etapas RMSE: "
            f"{rmse_dos_20:.4f}"
        )

    # ==================================================
    # COMPARACIÓN
    # ==================================================

    mejora_mae = (
        (
            mae_base
            - mae_dos
        )
        / mae_base
    ) * 100

    mejora_rmse = (
        (
            rmse_base
            - rmse_dos
        )
        / rmse_base
    ) * 100

    cambio_r2 = (
        r2_dos
        - r2_base
    )

    print()
    print("=" * 70)
    print("COMPARACIÓN FINAL")
    print("=" * 70)

    print(
        f"MAE base     : {mae_base:.4f}"
    )

    print(
        f"MAE dos etapas: {mae_dos:.4f}"
    )

    print()

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

    print()
    print("=" * 70)

    if mae_dos < mae_base:
        print(
            "✅ El modelo de dos etapas "
            "mejora el MAE global."
        )
    else:
        print(
            "❌ El modelo de dos etapas "
            "NO mejora el MAE global."
        )

    print()
    print(
        "modelo_demanda.pkl NO fue modificado."
    )


if __name__ == "__main__":
    main()