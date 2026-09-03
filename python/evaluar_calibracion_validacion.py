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


def evaluar_metricas(y_real, pred):

    return {
        "mae": mean_absolute_error(
            y_real,
            pred,
        ),
        "rmse": np.sqrt(
            mean_squared_error(
                y_real,
                pred,
            )
        ),
        "r2": r2_score(
            y_real,
            pred,
        ),
    }


def main():

    print("=" * 70)
    print("CALIBRACIÓN CORRECTA CON VALIDACIÓN TEMPORAL")
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

    # ==================================================
    # TEST FINAL
    # ==================================================

    indice_test = int(
        len(df) * 0.80
    )

    desarrollo_completo = df.iloc[
        :indice_test
    ].copy()

    test = df.iloc[
        indice_test:
    ].copy()

    # ==================================================
    # VALIDACIÓN TEMPORAL DENTRO DE DESARROLLO
    # ==================================================

    indice_validacion = int(
        len(desarrollo_completo) * 0.80
    )

    train = desarrollo_completo.iloc[
        :indice_validacion
    ].copy()

    validacion = desarrollo_completo.iloc[
        indice_validacion:
    ].copy()

    print()
    print(
        f"Train: {len(train)}"
    )

    print(
        f"Validación: {len(validacion)}"
    )

    print(
        f"Test final: {len(test)}"
    )

    print()
    print(
        f"Train: "
        f"{train['fecha'].min().date()} "
        f"→ "
        f"{train['fecha'].max().date()}"
    )

    print(
        f"Validación: "
        f"{validacion['fecha'].min().date()} "
        f"→ "
        f"{validacion['fecha'].max().date()}"
    )

    print(
        f"Test: "
        f"{test['fecha'].min().date()} "
        f"→ "
        f"{test['fecha'].max().date()}"
    )

    # ==================================================
    # MODELO BASE DE REGRESIÓN
    # ==================================================

    regresor = crear_regresor()

    regresor.fit(
        train[FEATURES],
        train["demanda"],
    )

    pred_validacion = regresor.predict(
        validacion[FEATURES]
    )

    pred_validacion = np.maximum(
        pred_validacion,
        0,
    )

    metricas_base = evaluar_metricas(
        validacion["demanda"],
        pred_validacion,
    )

    print()
    print("=" * 70)
    print("BASE EN VALIDACIÓN")
    print("=" * 70)

    print(
        f"MAE : {metricas_base['mae']:.4f}"
    )

    print(
        f"RMSE: {metricas_base['rmse']:.4f}"
    )

    print(
        f"R²  : {metricas_base['r2']:.4f}"
    )

    # ==================================================
    # DETECTOR DE PICO
    # ==================================================

    train = train.copy()
    validacion = validacion.copy()

    train["es_pico"] = (
        train["demanda"]
        >= UMBRAL_PICO
    ).astype(int)

    validacion["es_pico"] = (
        validacion["demanda"]
        >= UMBRAL_PICO
    ).astype(int)

    clasificador = crear_clasificador()

    clasificador.fit(
        train[FEATURES],
        train["es_pico"],
    )

    prob_validacion = (
        clasificador.predict_proba(
            validacion[FEATURES]
        )[:, 1]
    )

    # ==================================================
    # BUSCAR UMBRAL + FACTOR EN VALIDACIÓN
    # ==================================================

    umbrales = [
        0.40,
        0.45,
        0.50,
        0.55,
        0.60,
        0.65,
        0.70,
    ]

    factores = [
        1.00,
        1.02,
        1.05,
        1.08,
        1.10,
        1.15,
    ]

    resultados = []

    for umbral in umbrales:

        mascara = (
            prob_validacion
            >= umbral
        )

        for factor in factores:

            pred = (
                pred_validacion.copy()
            )

            pred[mascara] = (
                pred[mascara]
                * factor
            )

            pred = np.maximum(
                pred,
                0,
            )

            metricas = evaluar_metricas(
                validacion["demanda"],
                pred,
            )

            resultados.append({
                "umbral": umbral,
                "factor": factor,
                "mae": metricas["mae"],
                "rmse": metricas["rmse"],
                "r2": metricas["r2"],
                "casos_corregidos": int(
                    mascara.sum()
                ),
            })

    resultados_df = pd.DataFrame(
        resultados
    )

    resultados_df = resultados_df.sort_values(
        "mae"
    )

    print()
    print("=" * 70)
    print("MEJORES COMBINACIONES EN VALIDACIÓN")
    print("=" * 70)

    print(
        resultados_df
        .head(10)
        .to_string(
            index=False
        )
    )

    mejor = resultados_df.iloc[0]

    mejor_umbral = float(
        mejor["umbral"]
    )

    mejor_factor = float(
        mejor["factor"]
    )

    print()
    print("=" * 70)
    print("CONFIGURACIÓN ELEGIDA")
    print("=" * 70)

    print(
        f"Umbral: {mejor_umbral:.2f}"
    )

    print(
        f"Factor: {mejor_factor:.2f}"
    )

    print(
        f"MAE validación: "
        f"{mejor['mae']:.4f}"
    )

    # ==================================================
    # ENTRENAR NUEVAMENTE CON TODO DESARROLLO
    # ==================================================

    regresor_final = crear_regresor()

    regresor_final.fit(
        desarrollo_completo[FEATURES],
        desarrollo_completo["demanda"],
    )

    clasificador_final = crear_clasificador()

    desarrollo_completo = (
        desarrollo_completo.copy()
    )

    desarrollo_completo["es_pico"] = (
        desarrollo_completo["demanda"]
        >= UMBRAL_PICO
    ).astype(int)

    clasificador_final.fit(
        desarrollo_completo[FEATURES],
        desarrollo_completo["es_pico"],
    )

    # Predicción test
    pred_test = regresor_final.predict(
        test[FEATURES]
    )

    pred_test = np.maximum(
        pred_test,
        0,
    )

    prob_test = (
        clasificador_final.predict_proba(
            test[FEATURES]
        )[:, 1]
    )

    mascara_test = (
        prob_test
        >= mejor_umbral
    )

    pred_calibrada = (
        pred_test.copy()
    )

    pred_calibrada[mascara_test] = (
        pred_calibrada[mascara_test]
        * mejor_factor
    )

    pred_calibrada = np.maximum(
        pred_calibrada,
        0,
    )

    # ==================================================
    # TEST FINAL
    # ==================================================

    metricas_test_base = evaluar_metricas(
        test["demanda"],
        pred_test,
    )

    metricas_test_calibrado = (
        evaluar_metricas(
            test["demanda"],
            pred_calibrada,
        )
    )

    print()
    print("=" * 70)
    print("TEST FINAL")
    print("=" * 70)

    print()
    print("MODELO BASE")

    print(
        f"MAE : "
        f"{metricas_test_base['mae']:.4f}"
    )

    print(
        f"RMSE: "
        f"{metricas_test_base['rmse']:.4f}"
    )

    print(
        f"R²  : "
        f"{metricas_test_base['r2']:.4f}"
    )

    print()
    print(
        "MODELO CALIBRADO"
    )

    print(
        f"MAE : "
        f"{metricas_test_calibrado['mae']:.4f}"
    )

    print(
        f"RMSE: "
        f"{metricas_test_calibrado['rmse']:.4f}"
    )

    print(
        f"R²  : "
        f"{metricas_test_calibrado['r2']:.4f}"
    )

    # ==================================================
    # PICOS >=20
    # ==================================================

    y_test = test["demanda"].to_numpy()

    mascara_picos = (
        y_test
        >= 20
    )

    if mascara_picos.sum() > 0:

        mae_picos_base = (
            mean_absolute_error(
                y_test[
                    mascara_picos
                ],
                pred_test[
                    mascara_picos
                ],
            )
        )

        mae_picos_calibrado = (
            mean_absolute_error(
                y_test[
                    mascara_picos
                ],
                pred_calibrada[
                    mascara_picos
                ],
            )
        )

        rmse_picos_base = np.sqrt(
            mean_squared_error(
                y_test[
                    mascara_picos
                ],
                pred_test[
                    mascara_picos
                ],
            )
        )

        rmse_picos_calibrado = np.sqrt(
            mean_squared_error(
                y_test[
                    mascara_picos
                ],
                pred_calibrada[
                    mascara_picos
                ],
            )
        )

        print()
        print("=" * 70)
        print("PICOS >=20")
        print("=" * 70)

        print(
            f"Registros: "
            f"{mascara_picos.sum()}"
        )

        print()

        print(
            f"Base MAE : "
            f"{mae_picos_base:.4f}"
        )

        print(
            f"Calibrado MAE: "
            f"{mae_picos_calibrado:.4f}"
        )

        print()

        print(
            f"Base RMSE : "
            f"{rmse_picos_base:.4f}"
        )

        print(
            f"Calibrado RMSE: "
            f"{rmse_picos_calibrado:.4f}"
        )

    # ==================================================
    # DECISIÓN
    # ==================================================

    mejora_mae = (
        (
            metricas_test_base["mae"]
            - metricas_test_calibrado["mae"]
        )
        / metricas_test_base["mae"]
    ) * 100

    print()
    print("=" * 70)
    print("DECISIÓN FINAL")
    print("=" * 70)

    print(
        f"Mejora MAE en test: "
        f"{mejora_mae:+.2f}%"
    )

    if (
        metricas_test_calibrado["mae"]
        < metricas_test_base["mae"]
    ):

        print(
            "✅ La calibración mejora "
            "el MAE en test."
        )

    else:

        print(
            "❌ La calibración no mejora "
            "el MAE en test."
        )

    print()
    print(
        "modelo_demanda.pkl NO fue modificado."
    )


if __name__ == "__main__":
    main()