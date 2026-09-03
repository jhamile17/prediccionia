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


def metricas(y_real, pred):

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
    print("CALIBRACIÓN ESCALONADA DE PICOS")
    print("=" * 70)

    df = pd.read_csv(
        DATASET_PATH
    )

    df["fecha"] = pd.to_datetime(
        df["fecha"]
    )

    df = df.sort_values(
        ["fecha", "producto_id"]
    ).reset_index(
        drop=True
    )

    # ==================================================
    # TEST FINAL
    # ==================================================

    indice_test = int(
        len(df) * 0.80
    )

    desarrollo = df.iloc[
        :indice_test
    ].copy()

    test = df.iloc[
        indice_test:
    ].copy()

    # ==================================================
    # VALIDACIÓN
    # ==================================================

    indice_validacion = int(
        len(desarrollo) * 0.80
    )

    train = desarrollo.iloc[
        :indice_validacion
    ].copy()

    validacion = desarrollo.iloc[
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
        f"Test: {len(test)}"
    )

    print()
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
    # MODELO REGRESOR
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

    # ==================================================
    # CLASIFICADOR
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
    # BUSCAR CONFIGURACIÓN ESCALONADA
    # ==================================================

    configuraciones = [
        (0.40, 1.02, 1.05, 1.08),
        (0.40, 1.02, 1.05, 1.10),
        (0.40, 1.02, 1.08, 1.10),
        (0.40, 1.03, 1.05, 1.10),
        (0.45, 1.02, 1.05, 1.08),
        (0.45, 1.02, 1.05, 1.10),
        (0.50, 1.02, 1.05, 1.08),
        (0.50, 1.02, 1.05, 1.10),
        (0.50, 1.02, 1.08, 1.10),
        (0.55, 1.02, 1.05, 1.10),
        (0.60, 1.02, 1.05, 1.10),
    ]

    resultados = []

    for (
        umbral,
        factor_1,
        factor_2,
        factor_3,
    ) in configuraciones:

        pred = pred_validacion.copy()

        mascara_1 = (
            prob_validacion
            >= umbral
        ) & (
            prob_validacion
            < 0.60
        )

        mascara_2 = (
            prob_validacion
            >= 0.60
        ) & (
            prob_validacion
            < 0.80
        )

        mascara_3 = (
            prob_validacion
            >= 0.80
        )

        pred[mascara_1] *= factor_1
        pred[mascara_2] *= factor_2
        pred[mascara_3] *= factor_3

        pred = np.maximum(
            pred,
            0,
        )

        m = metricas(
            validacion["demanda"],
            pred,
        )

        resultados.append({
            "umbral": umbral,
            "factor_40_60": factor_1,
            "factor_60_80": factor_2,
            "factor_80_plus": factor_3,
            "mae": m["mae"],
            "rmse": m["rmse"],
            "r2": m["r2"],
        })

    resultados_df = pd.DataFrame(
        resultados
    ).sort_values(
        "mae"
    )

    print()
    print("=" * 70)
    print("MEJORES CONFIGURACIONES EN VALIDACIÓN")
    print("=" * 70)

    print(
        resultados_df.head(10).to_string(
            index=False
        )
    )

    mejor = resultados_df.iloc[0]

    # ==================================================
    # ENTRENAR CON TODO DESARROLLO
    # ==================================================

    regresor_final = crear_regresor()

    regresor_final.fit(
        desarrollo[FEATURES],
        desarrollo["demanda"],
    )

    desarrollo_final = desarrollo.copy()

    desarrollo_final["es_pico"] = (
        desarrollo_final["demanda"]
        >= UMBRAL_PICO
    ).astype(int)

    clasificador_final = crear_clasificador()

    clasificador_final.fit(
        desarrollo_final[FEATURES],
        desarrollo_final["es_pico"],
    )

    # ==================================================
    # TEST FINAL
    # ==================================================

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

    umbral = float(
        mejor["umbral"]
    )

    factor_1 = float(
        mejor["factor_40_60"]
    )

    factor_2 = float(
        mejor["factor_60_80"]
    )

    factor_3 = float(
        mejor["factor_80_plus"]
    )

    pred_calibrada = pred_test.copy()

    mascara_1 = (
        prob_test >= umbral
    ) & (
        prob_test < 0.60
    )

    mascara_2 = (
        prob_test >= 0.60
    ) & (
        prob_test < 0.80
    )

    mascara_3 = (
        prob_test >= 0.80
    )

    pred_calibrada[mascara_1] *= factor_1
    pred_calibrada[mascara_2] *= factor_2
    pred_calibrada[mascara_3] *= factor_3

    pred_calibrada = np.maximum(
        pred_calibrada,
        0,
    )

    base = metricas(
        test["demanda"],
        pred_test,
    )

    calibrado = metricas(
        test["demanda"],
        pred_calibrada,
    )

    # ==================================================
    # PICOS >=20
    # ==================================================

    y_test = test["demanda"].to_numpy()

    mascara_picos = (
        y_test >= 20
    )

    mae_pico_base = mean_absolute_error(
        y_test[mascara_picos],
        pred_test[mascara_picos],
    )

    mae_pico_calibrado = mean_absolute_error(
        y_test[mascara_picos],
        pred_calibrada[mascara_picos],
    )

    # ==================================================
    # RESULTADOS
    # ==================================================

    print()
    print("=" * 70)
    print("CONFIGURACIÓN ELEGIDA")
    print("=" * 70)

    print(
        f"Umbral inicial: {umbral:.2f}"
    )

    print(
        f"40-60% : × {factor_1:.2f}"
    )

    print(
        f"60-80% : × {factor_2:.2f}"
    )

    print(
        f"80%+   : × {factor_3:.2f}"
    )

    print()
    print("=" * 70)
    print("TEST FINAL")
    print("=" * 70)

    print()
    print("BASE")

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
    print("CALIBRADO")

    print(
        f"MAE : {calibrado['mae']:.4f}"
    )

    print(
        f"RMSE: {calibrado['rmse']:.4f}"
    )

    print(
        f"R²  : {calibrado['r2']:.4f}"
    )

    print()
    print("=" * 70)
    print("PICOS >=20")
    print("=" * 70)

    print(
        f"MAE base     : "
        f"{mae_pico_base:.4f}"
    )

    print(
        f"MAE calibrado: "
        f"{mae_pico_calibrado:.4f}"
    )

    mejora = (
        (
            base["mae"]
            - calibrado["mae"]
        )
        / base["mae"]
    ) * 100

    print()
    print(
        f"Mejora MAE test: "
        f"{mejora:+.2f}%"
    )

    print()
    print("=" * 70)

    if calibrado["mae"] < base["mae"]:

        print(
            "✅ La calibración escalonada "
            "mejora el MAE."
        )

    else:

        print(
            "❌ La calibración escalonada "
            "no mejora el MAE."
        )

    print()
    print(
        "modelo_demanda.pkl NO fue modificado."
    )


if __name__ == "__main__":
    main()