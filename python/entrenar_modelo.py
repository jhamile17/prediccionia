import os
import sys
import numpy as np
import pandas as pd
import joblib

from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import (
    mean_absolute_error,
    mean_squared_error,
    r2_score,
)


# ============================================================
# CONFIGURACIÓN GENERAL
# ============================================================

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

DATASET_PATH = os.path.join(
    BASE_DIR,
    "storage",
    "app",
    "datasets",
    "dataset_demanda.csv"
)

MODEL_PATH = os.path.join(
    BASE_DIR,
    "python",
    "modelos",
    "modelo_demanda.pkl"
)


# ============================================================
# VARIABLES DEL MODELO
# ============================================================

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


# ============================================================
# CONFIGURACIÓN RANDOM FOREST
# ============================================================

RF_PARAMS = {
    "n_estimators": 400,
    "max_depth": 20,
    "min_samples_leaf": 2,
    "max_features": "sqrt",
    "random_state": 42,
    "n_jobs": -1,
}


# ============================================================
# PESOS CONTINUOS V5
# ============================================================

PESO_ALPHA = 0.03
PESO_UMBRAL = 10.0
PESO_MAXIMO = 2.0


# ============================================================
# FUNCIONES AUXILIARES
# ============================================================

def imprimir_separador(caracter="-", longitud=70):
    print(caracter * longitud)


def crear_pesos_demanda(y):
    """
    Asigna mayor peso a demandas superiores al umbral.

    Fórmula:

        peso = 1 + alpha * max(demanda - umbral, 0)

    Después se limita al máximo establecido.
    """

    y_array = np.asarray(y, dtype=float)

    exceso = np.maximum(
        y_array - PESO_UMBRAL,
        0
    )

    pesos = 1.0 + PESO_ALPHA * exceso

    pesos = np.minimum(
        pesos,
        PESO_MAXIMO
    )

    return pesos


def calcular_metricas(y_real, prediccion):
    """
    Calcula métricas generales y métricas específicas
    para demandas altas.
    """

    y_real = np.asarray(y_real)
    prediccion = np.asarray(prediccion)

    mae = mean_absolute_error(
        y_real,
        prediccion
    )

    rmse = np.sqrt(
        mean_squared_error(
            y_real,
            prediccion
        )
    )

    r2 = r2_score(
        y_real,
        prediccion
    )

    mascara_picos = y_real >= 20

    cantidad_picos = int(
        mascara_picos.sum()
    )

    if cantidad_picos > 0:

        mae_picos = mean_absolute_error(
            y_real[mascara_picos],
            prediccion[mascara_picos]
        )

        rmse_picos = np.sqrt(
            mean_squared_error(
                y_real[mascara_picos],
                prediccion[mascara_picos]
            )
        )

    else:

        mae_picos = np.nan
        rmse_picos = np.nan

    return {
        "mae": mae,
        "rmse": rmse,
        "r2": r2,
        "mae_picos": mae_picos,
        "rmse_picos": rmse_picos,
        "cantidad_picos": cantidad_picos,
    }


def entrenar_random_forest(X, y):
    """
    Entrena el Random Forest V5 utilizando
    pesos continuos de demanda.
    """

    pesos = crear_pesos_demanda(y)

    modelo = RandomForestRegressor(
        **RF_PARAMS
    )

    modelo.fit(
        X,
        y,
        sample_weight=pesos
    )

    return modelo, pesos


# ============================================================
# PROGRAMA PRINCIPAL
# ============================================================

def main():

    print("=" * 70)
    print(" SISTEMA INTELIGENTE DE PREDICCIÓN DE DEMANDA")
    print(" ENTRENAMIENTO V5 - PESOS CONTINUOS")
    print("=" * 70)


    # ========================================================
    # 1. CARGAR DATASET
    # ========================================================

    print("\n[1/8] Cargando dataset...")

    if not os.path.exists(DATASET_PATH):
        print("ERROR: No existe el dataset:")
        print(DATASET_PATH)
        sys.exit(1)

    df = pd.read_csv(
        DATASET_PATH
    )

    print("Dataset cargado correctamente.")
    print(f"Registros : {len(df)}")
    print(f"Columnas  : {len(df.columns)}")


    # ========================================================
    # 2. VALIDAR ESTRUCTURA
    # ========================================================

    print("\n[2/8] Validando estructura del dataset...")

    columnas_requeridas = FEATURES + [
        "fecha",
        "demanda",
        "producto",
    ]

    faltantes = [
        columna
        for columna in columnas_requeridas
        if columna not in df.columns
    ]

    if faltantes:

        print("\nERROR: faltan columnas:")

        for columna in faltantes:
            print(f"  - {columna}")

        sys.exit(1)

    print("Todas las columnas requeridas existen.")


    # ========================================================
    # 3. PREPARAR DATOS
    # ========================================================

    print("\n[3/8] Ordenando datos cronológicamente...")

    df["fecha"] = pd.to_datetime(
        df["fecha"],
        errors="coerce"
    )

    if df["fecha"].isna().any():
        print("ERROR: Existen fechas inválidas.")
        sys.exit(1)

    df = df.sort_values(
        ["fecha", "producto_id"]
    ).reset_index(drop=True)

    print(
        "Periodo del dataset: "
        f"{df['fecha'].min().date()} → "
        f"{df['fecha'].max().date()}"
    )

    print("\nValidando valores faltantes...")

    nulos_modelo = df[FEATURES].isna().sum()

    columnas_con_nulos = nulos_modelo[
        nulos_modelo > 0
    ]

    if len(columnas_con_nulos) > 0:

        print("\nERROR: existen valores faltantes:")

        print(columnas_con_nulos)

        sys.exit(1)

    print(
        "No existen valores faltantes "
        "en las variables del modelo."
    )


    # ========================================================
    # 4. DIVISIÓN TEMPORAL
    # ========================================================

    print("\n[4/8] Dividiendo datos temporalmente...")

    n_total = len(df)

    corte_test = int(
        n_total * 0.80
    )

    desarrollo = df.iloc[
        :corte_test
    ].copy()

    test = df.iloc[
        corte_test:
    ].copy()


    print("\nDESARROLLO:")
    print(
        f"Registros: {len(desarrollo)}"
    )

    print(
        f"Periodo: "
        f"{desarrollo['fecha'].min().date()} → "
        f"{desarrollo['fecha'].max().date()}"
    )


    print("\nPRUEBA FINAL:")
    print(
        f"Registros: {len(test)}"
    )

    print(
        f"Periodo: "
        f"{test['fecha'].min().date()} → "
        f"{test['fecha'].max().date()}"
    )


    # ========================================================
    # 5. VALIDACIÓN TEMPORAL
    # ========================================================

    print("\n[5/8] Creando validación temporal...")

    corte_validacion = int(
        len(desarrollo) * 0.80
    )

    train = desarrollo.iloc[
        :corte_validacion
    ].copy()

    validacion = desarrollo.iloc[
        corte_validacion:
    ].copy()


    print("\nENTRENAMIENTO:")
    print(
        f"Registros: {len(train)}"
    )

    print(
        f"Periodo: "
        f"{train['fecha'].min().date()} → "
        f"{train['fecha'].max().date()}"
    )


    print("\nVALIDACIÓN:")
    print(
        f"Registros: {len(validacion)}"
    )

    print(
        f"Periodo: "
        f"{validacion['fecha'].min().date()} → "
        f"{validacion['fecha'].max().date()}"
    )


    # ========================================================
    # PREPARAR MATRICES
    # ========================================================

    X_train = train[FEATURES]
    y_train = train["demanda"]

    X_valid = validacion[FEATURES]
    y_valid = validacion["demanda"]

    X_dev = desarrollo[FEATURES]
    y_dev = desarrollo["demanda"]

    X_test = test[FEATURES]
    y_test = test["demanda"]


    # ========================================================
    # 6. VALIDACIÓN V5
    # ========================================================

    print("\n[6/8] Calculando pesos continuos...")

    print(
        f"Alpha       : {PESO_ALPHA}"
    )

    print(
        f"Umbral      : {PESO_UMBRAL}"
    )

    print(
        f"Peso máximo : {PESO_MAXIMO}"
    )


    pesos_train = crear_pesos_demanda(
        y_train
    )

    print(
        f"Peso mínimo : {pesos_train.min():.2f}"
    )

    print(
        f"Peso máximo : {pesos_train.max():.2f}"
    )

    print(
        f"Peso promedio: {pesos_train.mean():.4f}"
    )


    print("\nEntrenando modelo sobre entrenamiento...")


    modelo_validacion = RandomForestRegressor(
        **RF_PARAMS
    )

    modelo_validacion.fit(
        X_train,
        y_train,
        sample_weight=pesos_train
    )


    pred_validacion = modelo_validacion.predict(
        X_valid
    )

    metricas_validacion = calcular_metricas(
        y_valid,
        pred_validacion
    )


    print("\n" + "=" * 70)
    print(" RESULTADO VALIDACIÓN")
    print("=" * 70)

    print(
        f"MAE  : {metricas_validacion['mae']:.4f}"
    )

    print(
        f"RMSE : {metricas_validacion['rmse']:.4f}"
    )

    print(
        f"R²   : {metricas_validacion['r2']:.4f}"
    )

    print(
        f"MAE picos >=20 : "
        f"{metricas_validacion['mae_picos']:.4f}"
    )

    print(
        f"RMSE picos >=20: "
        f"{metricas_validacion['rmse_picos']:.4f}"
    )


    # ========================================================
    # 7. MODELO FINAL
    # ========================================================

    print("\n[7/8] Entrenando modelo final...")

    pesos_desarrollo = crear_pesos_demanda(
        y_dev
    )

    modelo_final = RandomForestRegressor(
        **RF_PARAMS
    )

    modelo_final.fit(
        X_dev,
        y_dev,
        sample_weight=pesos_desarrollo
    )

    print(
        "Modelo final entrenado correctamente."
    )


    # ========================================================
    # 8. EVALUACIÓN TEST
    # ========================================================

    print("\n[8/8] Evaluando con datos de prueba...")

    pred_test = modelo_final.predict(
        X_test
    )

    metricas_test = calcular_metricas(
        y_test,
        pred_test
    )


    print("\n" + "=" * 70)
    print(" RESULTADOS FINALES DEL MODELO V5")
    print("=" * 70)

    print(
        f"MAE  : {metricas_test['mae']:.4f}"
    )

    print(
        f"RMSE : {metricas_test['rmse']:.4f}"
    )

    print(
        f"R²   : {metricas_test['r2']:.4f}"
    )


    # ========================================================
    # ANÁLISIS DE DEMANDA ALTA
    # ========================================================

    print("\n" + "-" * 70)
    print(" ANÁLISIS DE DEMANDA ALTA")
    print("-" * 70)

    print(
        f"Registros con demanda >=20: "
        f"{metricas_test['cantidad_picos']}"
    )

    print(
        f"MAE picos : "
        f"{metricas_test['mae_picos']:.4f}"
    )

    print(
        f"RMSE picos: "
        f"{metricas_test['rmse_picos']:.4f}"
    )


    # ========================================================
    # IMPORTANCIA DE VARIABLES
    # ========================================================

    print("\n" + "-" * 70)
    print(" IMPORTANCIA DE VARIABLES")
    print("-" * 70)

    importancias = pd.Series(
        modelo_final.feature_importances_,
        index=FEATURES
    ).sort_values(
        ascending=False
    )

    for variable, importancia in importancias.items():

        print(
            f"{variable:<25} "
            f"{importancia:.4f}"
        )


    # ========================================================
    # EJEMPLOS DE PREDICCIÓN
    # ========================================================

    print("\n" + "-" * 70)
    print(" EJEMPLOS DE PREDICCIÓN")
    print("-" * 70)

    ejemplos = test[
        [
            "fecha",
            "producto_id",
            "producto",
            "demanda",
        ]
    ].copy()

    ejemplos["predicho"] = pred_test

    ejemplos["error"] = (
        ejemplos["demanda"]
        - ejemplos["predicho"]
    )

    ejemplos["error_abs"] = (
        np.abs(ejemplos["error"])
    )

    print(
        ejemplos[
            [
                "fecha",
                "producto_id",
                "demanda",
                "predicho",
                "error_abs",
            ]
        ]
        .head(10)
        .to_string(index=False)
    )


    # ========================================================
    # COMPARACIÓN CONTRA V4
    # ========================================================

    V4_MAE = 1.0969
    V4_RMSE = 1.5715
    V4_R2 = 0.8592
    V4_MAE_PICOS = 3.7205

    mejora_mae = (
        (V4_MAE - metricas_test["mae"])
        / V4_MAE
    ) * 100

    mejora_rmse = (
        (V4_RMSE - metricas_test["rmse"])
        / V4_RMSE
    ) * 100

    cambio_r2 = (
        metricas_test["r2"]
        - V4_R2
    )

    mejora_picos = (
        (V4_MAE_PICOS - metricas_test["mae_picos"])
        / V4_MAE_PICOS
    ) * 100


    print("\n" + "=" * 70)
    print(" COMPARACIÓN V5 VS V4")
    print("=" * 70)

    print(
        f"MAE V4       : {V4_MAE:.4f}"
    )

    print(
        f"MAE V5       : {metricas_test['mae']:.4f}"
    )

    print(
        f"Mejora MAE   : {mejora_mae:+.2f}%"
    )

    print()

    print(
        f"RMSE V4      : {V4_RMSE:.4f}"
    )

    print(
        f"RMSE V5      : {metricas_test['rmse']:.4f}"
    )

    print(
        f"Mejora RMSE  : {mejora_rmse:+.2f}%"
    )

    print()

    print(
        f"R² V4        : {V4_R2:.4f}"
    )

    print(
        f"R² V5        : {metricas_test['r2']:.4f}"
    )

    print(
        f"Cambio R²    : {cambio_r2:+.4f}"
    )

    print()

    print(
        f"MAE picos V4 : {V4_MAE_PICOS:.4f}"
    )

    print(
        f"MAE picos V5 : {metricas_test['mae_picos']:.4f}"
    )

    print(
        f"Mejora picos : {mejora_picos:+.2f}%"
    )


    # ========================================================
    # PAQUETE DEL MODELO
    # ========================================================

    print("\nGuardando modelo...")

    paquete_modelo = {

        "modelo": modelo_final,

        "features": FEATURES,

        "mae": float(
            metricas_test["mae"]
        ),

        "rmse": float(
            metricas_test["rmse"]
        ),

        "r2": float(
            metricas_test["r2"]
        ),

        "mae_picos_test": float(
            metricas_test["mae_picos"]
        ),

        "rmse_picos_test": float(
            metricas_test["rmse_picos"]
        ),

        "cantidad_picos_test": int(
            metricas_test["cantidad_picos"]
        ),

        "mae_validacion": float(
            metricas_validacion["mae"]
        ),

        "rmse_validacion": float(
            metricas_validacion["rmse"]
        ),

        "r2_validacion": float(
            metricas_validacion["r2"]
        ),

        "fecha_test_inicio": str(
            test["fecha"].min().date()
        ),

        "fecha_test_fin": str(
            test["fecha"].max().date()
        ),

        "fecha_dataset_inicio": str(
            df["fecha"].min().date()
        ),

        "fecha_dataset_fin": str(
            df["fecha"].max().date()
        ),

        "registros_entrenamiento": int(
            len(desarrollo)
        ),

        "registros_prueba": int(
            len(test)
        ),

        "mejores_parametros": RF_PARAMS,

        "metodo_entrenamiento":
            "Random Forest con pesos continuos",

        "peso_alpha":
            PESO_ALPHA,

        "peso_umbral":
            PESO_UMBRAL,

        "peso_maximo":
            PESO_MAXIMO,

        "version_modelo":
            "V5-pesos-continuos",

        "dataset_version":
            "simulacion_demanda_v2",

        "fecha_entrenamiento":
            pd.Timestamp.now().strftime(
                "%Y-%m-%d %H:%M:%S"
            ),
    }


    joblib.dump(
        paquete_modelo,
        MODEL_PATH
    )


    print(
        "\nModelo guardado correctamente en:"
    )

    print(
        MODEL_PATH
    )


    # ========================================================
    # RESUMEN FINAL
    # ========================================================

    print("\n" + "=" * 70)
    print(" ENTRENAMIENTO V5 FINALIZADO")
    print("=" * 70)

    print(
        f"Dataset               : "
        f"{len(df)} registros"
    )

    print(
        f"Entrenamiento         : "
        f"{len(desarrollo)} registros"
    )

    print(
        f"Prueba                : "
        f"{len(test)} registros"
    )

    print(
        f"MAE final             : "
        f"{metricas_test['mae']:.4f}"
    )

    print(
        f"RMSE final            : "
        f"{metricas_test['rmse']:.4f}"
    )

    print(
        f"R² final              : "
        f"{metricas_test['r2']:.4f}"
    )

    print(
        f"MAE validación        : "
        f"{metricas_validacion['mae']:.4f}"
    )

    print(
        f"MAE picos >=20        : "
        f"{metricas_test['mae_picos']:.4f}"
    )

    print(
        "Pesos continuos       : "
        f"alpha={PESO_ALPHA}, "
        f"máximo={PESO_MAXIMO}"
    )

    print(
        "Modelo                : "
        "Random Forest 400/20/2/sqrt"
    )

    print(
        "Versión               : V5-pesos-continuos"
    )

    print(
        f"Archivo               : "
        f"{MODEL_PATH}"
    )

    print("=" * 70)


if __name__ == "__main__":
    main()