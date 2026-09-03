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
        "nombre": "alpha=0.01 max=1.5",
        "alpha": 0.01,
        "max_peso": 1.5,
    },
    {
        "nombre": "alpha=0.01 max=2.0",
        "alpha": 0.01,
        "max_peso": 2.0,
    },
    {
        "nombre": "alpha=0.02 max=1.5",
        "alpha": 0.02,
        "max_peso": 1.5,
    },
    {
        "nombre": "alpha=0.02 max=2.0",
        "alpha": 0.02,
        "max_peso": 2.0,
    },
    {
        "nombre": "alpha=0.02 max=2.5",
        "alpha": 0.02,
        "max_peso": 2.5,
    },
    {
        "nombre": "alpha=0.03 max=2.0",
        "alpha": 0.03,
        "max_peso": 2.0,
    },
    {
        "nombre": "alpha=0.03 max=2.5",
        "alpha": 0.03,
        "max_peso": 2.5,
    },
    {
        "nombre": "alpha=0.04 max=2.5",
        "alpha": 0.04,
        "max_peso": 2.5,
    },
    {
        "nombre": "alpha=0.05 max=3.0",
        "alpha": 0.05,
        "max_peso": 3.0,
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

    y = np.asarray(
        y,
        dtype=float,
    )

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


def entrenar_evaluar(
    train,
    validacion,
    configuracion,
):

    modelo = crear_modelo()

    pesos = crear_pesos(
        train["demanda"],
        configuracion["alpha"],
        configuracion["max_peso"],
    )

    modelo.fit(
        train[FEATURES],
        train["demanda"],
        sample_weight=pesos,
    )

    pred = modelo.predict(
        validacion[FEATURES]
    )

    pred = np.maximum(
        pred,
        0,
    )

    metricas = evaluar_metricas(
        validacion["demanda"],
        pred,
    )

    y = validacion["demanda"].to_numpy()

    mascara_picos = (
        y >= 20
    )

    if mascara_picos.sum() > 0:

        metricas["mae_picos"] = (
            mean_absolute_error(
                y[mascara_picos],
                pred[mascara_picos],
            )
        )

        metricas["rmse_picos"] = (
            np.sqrt(
                mean_squared_error(
                    y[mascara_picos],
                    pred[mascara_picos],
                )
            )
        )

    else:

        metricas["mae_picos"] = np.nan
        metricas["rmse_picos"] = np.nan

    return metricas


def main():

    print("=" * 70)
    print(
        "VALIDACIÓN TEMPORAL DE PESOS CONTINUOS"
    )
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

    desarrollo = df.iloc[
        :indice_test
    ].copy()

    test = df.iloc[
        indice_test:
    ].copy()

    # ==================================================
    # VALIDACIÓN DENTRO DE DESARROLLO
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
    # BASE EN VALIDACIÓN
    # ==================================================

    modelo_base = crear_modelo()

    modelo_base.fit(
        train[FEATURES],
        train["demanda"],
    )

    pred_base_validacion = (
        modelo_base.predict(
            validacion[FEATURES]
        )
    )

    pred_base_validacion = np.maximum(
        pred_base_validacion,
        0,
    )

    base_validacion = evaluar_metricas(
        validacion["demanda"],
        pred_base_validacion,
    )

    print()
    print("=" * 70)
    print("MODELO BASE EN VALIDACIÓN")
    print("=" * 70)

    print(
        f"MAE : "
        f"{base_validacion['mae']:.4f}"
    )

    print(
        f"RMSE: "
        f"{base_validacion['rmse']:.4f}"
    )

    print(
        f"R²  : "
        f"{base_validacion['r2']:.4f}"
    )

    # ==================================================
    # EXPERIMENTOS
    # ==================================================

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

        metricas = entrenar_evaluar(
            train,
            validacion,
            configuracion,
        )

        resultado = {
            "configuracion": configuracion["nombre"],
            "alpha": configuracion["alpha"],
            "max_peso": configuracion["max_peso"],
            "mae": metricas["mae"],
            "rmse": metricas["rmse"],
            "r2": metricas["r2"],
            "mae_picos": metricas["mae_picos"],
            "rmse_picos": metricas["rmse_picos"],
        }

        resultados.append(
            resultado
        )

        print(
            f"MAE={metricas['mae']:.4f} | "
            f"RMSE={metricas['rmse']:.4f} | "
            f"R²={metricas['r2']:.4f}"
        )

    resultados_df = pd.DataFrame(
        resultados
    )

    # ==================================================
    # RANKING
    # ==================================================

    resultados_df = resultados_df.sort_values(
        "mae"
    ).reset_index(
        drop=True
    )

    print()
    print("=" * 70)
    print("RANKING EN VALIDACIÓN")
    print("=" * 70)

    print(
        resultados_df.to_string(
            index=False
        )
    )

    mejor = resultados_df.iloc[0]

    print()
    print("=" * 70)
    print("CONFIGURACIÓN ELEGIDA")
    print("=" * 70)

    print(
        f"Configuración: "
        f"{mejor['configuracion']}"
    )

    print(
        f"MAE validación: "
        f"{mejor['mae']:.4f}"
    )

    print(
        f"RMSE validación: "
        f"{mejor['rmse']:.4f}"
    )

    print(
        f"R² validación: "
        f"{mejor['r2']:.4f}"
    )

    # ==================================================
    # ENTRENAMIENTO FINAL CON TODO DESARROLLO
    # ==================================================

    modelo_final = crear_modelo()

    pesos_finales = crear_pesos(
        desarrollo["demanda"],
        float(mejor["alpha"]),
        float(mejor["max_peso"]),
    )

    modelo_final.fit(
        desarrollo[FEATURES],
        desarrollo["demanda"],
        sample_weight=pesos_finales,
    )

    # ==================================================
    # TEST FINAL
    # ==================================================

    pred_test_base = (
        modelo_base.predict(
            test[FEATURES]
        )
    )

    pred_test_base = np.maximum(
        pred_test_base,
        0,
    )

    pred_test_pesos = (
        modelo_final.predict(
            test[FEATURES]
        )
    )

    pred_test_pesos = np.maximum(
        pred_test_pesos,
        0,
    )

    metricas_base = evaluar_metricas(
        test["demanda"],
        pred_test_base,
    )

    metricas_pesos = evaluar_metricas(
        test["demanda"],
        pred_test_pesos,
    )

    print()
    print("=" * 70)
    print("TEST FINAL")
    print("=" * 70)

    print()
    print("MODELO BASE")

    print(
        f"MAE : "
        f"{metricas_base['mae']:.4f}"
    )

    print(
        f"RMSE: "
        f"{metricas_base['rmse']:.4f}"
    )

    print(
        f"R²  : "
        f"{metricas_base['r2']:.4f}"
    )

    print()
    print(
        "MODELO CON PESOS"
    )

    print(
        f"MAE : "
        f"{metricas_pesos['mae']:.4f}"
    )

    print(
        f"RMSE: "
        f"{metricas_pesos['rmse']:.4f}"
    )

    print(
        f"R²  : "
        f"{metricas_pesos['r2']:.4f}"
    )

    # ==================================================
    # PICOS
    # ==================================================

    y_test = test["demanda"].to_numpy()

    mascara_picos = (
        y_test >= 20
    )

    mae_picos_base = mean_absolute_error(
        y_test[mascara_picos],
        pred_test_base[mascara_picos],
    )

    mae_picos_pesos = mean_absolute_error(
        y_test[mascara_picos],
        pred_test_pesos[mascara_picos],
    )

    rmse_picos_base = np.sqrt(
        mean_squared_error(
            y_test[mascara_picos],
            pred_test_base[mascara_picos],
        )
    )

    rmse_picos_pesos = np.sqrt(
        mean_squared_error(
            y_test[mascara_picos],
            pred_test_pesos[mascara_picos],
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
        f"MAE base: "
        f"{mae_picos_base:.4f}"
    )

    print(
        f"MAE pesos: "
        f"{mae_picos_pesos:.4f}"
    )

    print()
    print(
        f"RMSE base: "
        f"{rmse_picos_base:.4f}"
    )

    print(
        f"RMSE pesos: "
        f"{rmse_picos_pesos:.4f}"
    )

    # ==================================================
    # MEJORAS
    # ==================================================

    mejora_mae = (
        (
            metricas_base["mae"]
            - metricas_pesos["mae"]
        )
        / metricas_base["mae"]
    ) * 100

    mejora_rmse = (
        (
            metricas_base["rmse"]
            - metricas_pesos["rmse"]
        )
        / metricas_base["rmse"]
    ) * 100

    mejora_picos = (
        (
            mae_picos_base
            - mae_picos_pesos
        )
        / mae_picos_base
    ) * 100

    print()
    print("=" * 70)
    print("COMPARACIÓN FINAL")
    print("=" * 70)

    print(
        f"Mejora MAE: "
        f"{mejora_mae:+.2f}%"
    )

    print(
        f"Mejora RMSE: "
        f"{mejora_rmse:+.2f}%"
    )

    print(
        f"Mejora MAE picos: "
        f"{mejora_picos:+.2f}%"
    )

    print()
    print("=" * 70)

    if (
        metricas_pesos["mae"]
        < metricas_base["mae"]
    ):

        print(
            "✅ Los pesos continuos "
            "mejoran el MAE en el test final."
        )

    else:

        print(
            "❌ Los pesos continuos "
            "no mejoran el MAE en el test final."
        )

    print()
    print(
        "modelo_demanda.pkl NO fue modificado."
    )


if __name__ == "__main__":
    main()