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


def crear_modelo():

    return RandomForestRegressor(
        n_estimators=400,
        max_depth=20,
        min_samples_leaf=2,
        max_features="sqrt",
        random_state=42,
        n_jobs=-1,
    )


def evaluar_modelo_global(df):

    # Corte temporal global: 80% desarrollo / 20% test
    indice_test = int(len(df) * 0.80)

    desarrollo = df.iloc[
        :indice_test
    ].copy()

    test = df.iloc[
        indice_test:
    ].copy()

    modelo = crear_modelo()

    modelo.fit(
        desarrollo[FEATURES],
        desarrollo["demanda"],
    )

    pred = modelo.predict(
        test[FEATURES]
    )

    # No permitir predicciones negativas
    pred = np.maximum(
        pred,
        0
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

    test = test.copy()

    test["prediccion"] = pred

    return {
        "modelo": "Modelo global",
        "mae": mae,
        "rmse": rmse,
        "r2": r2,
        "test": test,
    }


def evaluar_modelos_individuales(df):

    resultados = []

    predicciones = []

    # Cada producto tendrá su propio corte temporal.
    for producto_id, grupo in df.groupby(
        "producto_id",
        sort=True
    ):

        grupo = grupo.sort_values(
            "fecha"
        ).reset_index(
            drop=True
        )

        # --------------------------------------------------
        # CORTE TEMPORAL POR PRODUCTO
        # --------------------------------------------------

        indice_test_producto = int(
            len(grupo) * 0.80
        )

        desarrollo = grupo.iloc[
            :indice_test_producto
        ].copy()

        test = grupo.iloc[
            indice_test_producto:
        ].copy()

        if (
            len(desarrollo) == 0
            or len(test) == 0
        ):
            continue

        # --------------------------------------------------
        # ENTRENAR MODELO INDIVIDUAL
        # --------------------------------------------------

        modelo = crear_modelo()

        modelo.fit(
            desarrollo[FEATURES],
            desarrollo["demanda"],
        )

        pred = modelo.predict(
            test[FEATURES]
        )

        # Evitar predicciones negativas
        pred = np.maximum(
            pred,
            0
        )

        # --------------------------------------------------
        # MÉTRICAS
        # --------------------------------------------------

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

        # R² puede ser indefinido si el conjunto
        # tuviera valores constantes.
        if test["demanda"].nunique() > 1:

            r2 = r2_score(
                test["demanda"],
                pred,
            )

        else:

            r2 = np.nan

        # --------------------------------------------------
        # PICOS DE DEMANDA
        # --------------------------------------------------

        picos = (
            test["demanda"].to_numpy()
            >= 20
        )

        if picos.sum() > 0:

            mae_picos = mean_absolute_error(
                test.loc[
                    picos,
                    "demanda"
                ],
                pred[picos],
            )

        else:

            mae_picos = np.nan

        # --------------------------------------------------
        # GUARDAR RESULTADO
        # --------------------------------------------------

        resultados.append({
            "producto_id": producto_id,
            "producto": test["producto"].iloc[0],
            "registros_desarrollo": len(
                desarrollo
            ),
            "registros_test": len(
                test
            ),
            "mae": mae,
            "rmse": rmse,
            "r2": r2,
            "picos_20+": int(
                picos.sum()
            ),
            "mae_picos": mae_picos,
        })

        # --------------------------------------------------
        # GUARDAR PREDICCIONES
        # --------------------------------------------------

        temporal = test[
            [
                "fecha",
                "producto_id",
                "producto",
                "demanda",
            ]
        ].copy()

        temporal["prediccion"] = pred

        predicciones.append(
            temporal
        )

    resultados_df = pd.DataFrame(
        resultados
    )

    if not predicciones:

        raise RuntimeError(
            "No se generaron predicciones "
            "para ningún producto."
        )

    predicciones_df = pd.concat(
        predicciones,
        ignore_index=True
    )

    return (
        resultados_df,
        predicciones_df,
    )


def main():

    print("=" * 70)
    print(
        "EXPERIMENTO: UN MODELO RANDOM FOREST POR PRODUCTO"
    )
    print("=" * 70)

    # --------------------------------------------------
    # CARGAR DATASET
    # --------------------------------------------------

    df = pd.read_csv(
        DATASET_PATH
    )

    df["fecha"] = pd.to_datetime(
        df["fecha"]
    )

    # Orden temporal global
    df = df.sort_values(
        [
            "fecha",
            "producto_id",
        ]
    ).reset_index(
        drop=True
    )

    print()
    print(
        f"Dataset: {len(df)}"
    )

    print(
        f"Fecha: "
        f"{df['fecha'].min().date()} "
        f"→ "
        f"{df['fecha'].max().date()}"
    )

    print(
        f"Productos: "
        f"{df['producto_id'].nunique()}"
    )

    # --------------------------------------------------
    # MODELO GLOBAL
    # --------------------------------------------------

    global_resultado = evaluar_modelo_global(
        df
    )

    print()
    print("=" * 70)
    print("MODELO GLOBAL")
    print("=" * 70)

    print(
        f"MAE : "
        f"{global_resultado['mae']:.4f}"
    )

    print(
        f"RMSE: "
        f"{global_resultado['rmse']:.4f}"
    )

    print(
        f"R²  : "
        f"{global_resultado['r2']:.4f}"
    )

    print(
        f"Test: "
        f"{global_resultado['test']['fecha'].min().date()} "
        f"→ "
        f"{global_resultado['test']['fecha'].max().date()}"
    )

    # --------------------------------------------------
    # MODELOS INDIVIDUALES
    # --------------------------------------------------

    (
        resultados,
        predicciones,
    ) = evaluar_modelos_individuales(
        df
    )

    print()
    print("=" * 70)
    print("RESULTADOS POR PRODUCTO")
    print("=" * 70)

    print(
        resultados[
            [
                "producto_id",
                "producto",
                "registros_desarrollo",
                "registros_test",
                "mae",
                "rmse",
                "r2",
                "picos_20+",
                "mae_picos",
            ]
        ].to_string(
            index=False
        )
    )

    # --------------------------------------------------
    # MÉTRICAS GLOBALES DE LOS MODELOS INDIVIDUALES
    # --------------------------------------------------

    y_real = predicciones[
        "demanda"
    ].to_numpy()

    y_pred = predicciones[
        "prediccion"
    ].to_numpy()

    mae_individual = mean_absolute_error(
        y_real,
        y_pred,
    )

    rmse_individual = np.sqrt(
        mean_squared_error(
            y_real,
            y_pred,
        )
    )

    r2_individual = r2_score(
        y_real,
        y_pred,
    )

    # --------------------------------------------------
    # COMPARACIÓN GLOBAL
    # --------------------------------------------------

    print()
    print("=" * 70)
    print("COMPARACIÓN GLOBAL")
    print("=" * 70)

    print(
        f"Modelo global       MAE : "
        f"{global_resultado['mae']:.4f}"
    )

    print(
        f"Modelos individuales MAE: "
        f"{mae_individual:.4f}"
    )

    print()

    print(
        f"Modelo global       RMSE : "
        f"{global_resultado['rmse']:.4f}"
    )

    print(
        f"Modelos individuales RMSE: "
        f"{rmse_individual:.4f}"
    )

    print()

    print(
        f"Modelo global       R² : "
        f"{global_resultado['r2']:.4f}"
    )

    print(
        f"Modelos individuales R²: "
        f"{r2_individual:.4f}"
    )

    # --------------------------------------------------
    # MEJORAS
    # --------------------------------------------------

    mejora_mae = (
        (
            global_resultado["mae"]
            - mae_individual
        )
        / global_resultado["mae"]
    ) * 100

    mejora_rmse = (
        (
            global_resultado["rmse"]
            - rmse_individual
        )
        / global_resultado["rmse"]
    ) * 100

    cambio_r2 = (
        r2_individual
        - global_resultado["r2"]
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

    # --------------------------------------------------
    # COMPARACIÓN DE PICOS
    # --------------------------------------------------

    demanda_global = (
        global_resultado[
            "test"
        ]["demanda"]
        .to_numpy()
    )

    pred_global = (
        global_resultado[
            "test"
        ]["prediccion"]
        .to_numpy()
    )

    picos_global = (
        demanda_global
        >= 20
    )

    if picos_global.sum() > 0:

        mae_picos_global = mean_absolute_error(
            demanda_global[picos_global],
            pred_global[picos_global],
        )

        rmse_picos_global = np.sqrt(
            mean_squared_error(
                demanda_global[picos_global],
                pred_global[picos_global],
            )
        )

    else:

        mae_picos_global = np.nan
        rmse_picos_global = np.nan

    demanda_individual = (
        predicciones[
            "demanda"
        ].to_numpy()
    )

    pred_individual = (
        predicciones[
            "prediccion"
        ].to_numpy()
    )

    picos_individual = (
        demanda_individual
        >= 20
    )

    if picos_individual.sum() > 0:

        mae_picos_individual = mean_absolute_error(
            demanda_individual[
                picos_individual
            ],
            pred_individual[
                picos_individual
            ],
        )

        rmse_picos_individual = np.sqrt(
            mean_squared_error(
                demanda_individual[
                    picos_individual
                ],
                pred_individual[
                    picos_individual
                ],
            )
        )

    else:

        mae_picos_individual = np.nan
        rmse_picos_individual = np.nan

    print()
    print("=" * 70)
    print("COMPARACIÓN DE PICOS >=20")
    print("=" * 70)

    print(
        f"Modelo global:"
    )

    print(
        f"Registros: "
        f"{picos_global.sum()}"
    )

    print(
        f"MAE picos : "
        f"{mae_picos_global:.4f}"
    )

    print(
        f"RMSE picos: "
        f"{rmse_picos_global:.4f}"
    )

    print()

    print(
        f"Modelos individuales:"
    )

    print(
        f"Registros: "
        f"{picos_individual.sum()}"
    )

    print(
        f"MAE picos : "
        f"{mae_picos_individual:.4f}"
    )

    print(
        f"RMSE picos: "
        f"{rmse_picos_individual:.4f}"
    )

    print()
    print("=" * 70)

    # --------------------------------------------------
    # DECISIÓN
    # --------------------------------------------------

    if mae_individual < global_resultado["mae"]:

        print(
            "✅ Los modelos individuales "
            "mejoran el MAE global."
        )

    else:

        print(
            "❌ Los modelos individuales "
            "no mejoran el MAE global."
        )

    if (
        not np.isnan(mae_picos_individual)
        and not np.isnan(mae_picos_global)
        and mae_picos_individual
        < mae_picos_global
    ):

        print(
            "✅ También mejoran los picos."
        )

    else:

        print(
            "⚠️ No mejoran los picos."
        )

    print()
    print(
        "modelo_demanda.pkl NO fue modificado."
    )


if __name__ == "__main__":
    main()