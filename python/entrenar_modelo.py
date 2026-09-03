import os
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
# 1. RUTAS
# ============================================================

BASE_DIR = os.path.dirname(
    os.path.dirname(
        os.path.abspath(__file__)
    )
)

DATASET_PATH = os.path.join(
    BASE_DIR,
    "storage",
    "app",
    "datasets",
    "dataset_demanda.csv"
)

MODEL_DIR = os.path.join(
    BASE_DIR,
    "python",
    "modelos"
)

MODEL_PATH = os.path.join(
    MODEL_DIR,
    "modelo_demanda.pkl"
)


# ============================================================
# 2. CONFIGURACIÓN DEL MODELO
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

TARGET = "demanda"


# ============================================================
# 3. HIPERPARÁMETROS VALIDADOS
# ============================================================

MODELO_PARAMETROS = {
    "n_estimators": 400,
    "max_depth": 20,
    "min_samples_leaf": 2,
    "max_features": "sqrt",
    "random_state": 42,
    "n_jobs": -1,
}


# ============================================================
# 4. PESOS CONTINUOS PARA DEMANDA ALTA
# ============================================================

PESO_ALPHA = 0.02
PESO_MAXIMO = 2.0
PESO_UMBRAL = 10.0


def crear_pesos_demanda(y):
    """
    Asigna mayor peso a observaciones con demanda alta.

    Fórmula:

        peso = 1 + alpha * max(0, demanda - umbral)

    con límite máximo.

    Ejemplos con alpha=0.02 y max=2.0:

        demanda <= 10 -> peso 1.00
        demanda 15    -> peso 1.10
        demanda 20    -> peso 1.20
        demanda 30    -> peso 1.40
        demanda 40    -> peso 1.60
        demanda 50    -> peso 1.80
        demanda 60+   -> peso 2.00
    """

    valores = np.asarray(
        y,
        dtype=float
    )

    exceso = np.maximum(
        valores - PESO_UMBRAL,
        0
    )

    pesos = (
        1.0
        + PESO_ALPHA * exceso
    )

    pesos = np.minimum(
        pesos,
        PESO_MAXIMO
    )

    return pesos


# ============================================================
# 5. CREAR MODELO
# ============================================================

def crear_modelo():

    return RandomForestRegressor(
        n_estimators=MODELO_PARAMETROS["n_estimators"],
        max_depth=MODELO_PARAMETROS["max_depth"],
        min_samples_leaf=MODELO_PARAMETROS["min_samples_leaf"],
        max_features=MODELO_PARAMETROS["max_features"],
        random_state=MODELO_PARAMETROS["random_state"],
        n_jobs=MODELO_PARAMETROS["n_jobs"],
    )


# ============================================================
# 6. ENCABEZADO
# ============================================================

print("=" * 70)
print(
    " SISTEMA INTELIGENTE DE PREDICCIÓN DE DEMANDA"
)
print(
    " ENTRENAMIENTO V3 - PESOS CONTINUOS"
)
print("=" * 70)


# ============================================================
# 7. CARGAR DATASET
# ============================================================

print("\n[1/8] Cargando dataset...")

if not os.path.exists(DATASET_PATH):

    raise FileNotFoundError(
        f"No se encontró el dataset:\n"
        f"{DATASET_PATH}"
    )

df = pd.read_csv(
    DATASET_PATH
)

print(
    "Dataset cargado correctamente."
)

print(
    f"Registros : {len(df)}"
)

print(
    f"Columnas  : {len(df.columns)}"
)


# ============================================================
# 8. VALIDAR COLUMNAS
# ============================================================

print(
    "\n[2/8] Validando estructura del dataset..."
)

columnas_requeridas = [
    "fecha",
    TARGET,
] + FEATURES

columnas_faltantes = [
    columna
    for columna in columnas_requeridas
    if columna not in df.columns
]

if columnas_faltantes:

    raise ValueError(
        "Faltan las siguientes columnas:\n"
        + "\n".join(
            f"- {columna}"
            for columna in columnas_faltantes
        )
    )

print(
    "Todas las columnas requeridas existen."
)


# ============================================================
# 9. PREPARAR FECHA
# ============================================================

df["fecha"] = pd.to_datetime(
    df["fecha"],
    errors="coerce"
)

if df["fecha"].isna().any():

    cantidad = int(
        df["fecha"].isna().sum()
    )

    raise ValueError(
        f"Se encontraron {cantidad} fechas inválidas."
    )


# ============================================================
# 10. ORDEN CRONOLÓGICO
# ============================================================

print(
    "\n[3/8] Ordenando datos cronológicamente..."
)

df = df.sort_values(
    [
        "fecha",
        "producto_id",
    ]
).reset_index(
    drop=True
)

fecha_inicio_dataset = df[
    "fecha"
].min()

fecha_fin_dataset = df[
    "fecha"
].max()

print(
    f"Periodo del dataset: "
    f"{fecha_inicio_dataset.date()} "
    f"→ "
    f"{fecha_fin_dataset.date()}"
)


# ============================================================
# 11. VALIDAR VALORES NULOS
# ============================================================

print(
    "\nValidando valores faltantes..."
)

nulos = df[
    FEATURES + [TARGET]
].isnull().sum()

nulos = nulos[
    nulos > 0
]

if len(nulos) > 0:

    print(
        "\nSe encontraron valores faltantes:"
    )

    for columna, cantidad in nulos.items():

        print(
            f"  {columna}: {cantidad}"
        )

    raise ValueError(
        "El dataset contiene valores faltantes "
        "en variables utilizadas por el modelo."
    )

print(
    "No existen valores faltantes "
    "en las variables del modelo."
)


# ============================================================
# 12. CREAR X E Y
# ============================================================

X = df[
    FEATURES
].copy()

y = df[
    TARGET
].copy()


# ============================================================
# 13. DIVISIÓN TEMPORAL
# ============================================================

print(
    "\n[4/8] Dividiendo datos temporalmente..."
)

total = len(df)

# 80% desarrollo
# 20% test final

punto_test = int(
    total * 0.80
)

X_dev = X.iloc[
    :punto_test
].copy()

y_dev = y.iloc[
    :punto_test
].copy()

X_test = X.iloc[
    punto_test:
].copy()

y_test = y.iloc[
    punto_test:
].copy()

fecha_test_inicio = df.iloc[
    punto_test
]["fecha"]

fecha_test_fin = df.iloc[
    -1
]["fecha"]

print(
    "\nDESARROLLO:"
)

print(
    f"Registros: {len(X_dev)}"
)

print(
    f"Periodo: "
    f"{df.iloc[0]['fecha'].date()} "
    f"→ "
    f"{df.iloc[punto_test - 1]['fecha'].date()}"
)

print(
    "\nPRUEBA FINAL:"
)

print(
    f"Registros: {len(X_test)}"
)

print(
    f"Periodo: "
    f"{fecha_test_inicio.date()} "
    f"→ "
    f"{fecha_test_fin.date()}"
)


# ============================================================
# 14. DIVISIÓN ENTRENAMIENTO / VALIDACIÓN
# ============================================================

print(
    "\n[5/8] Creando validación temporal..."
)

# Dentro del 80% de desarrollo:
#
# 80% entrenamiento
# 20% validación

punto_validacion = int(
    len(X_dev) * 0.80
)

X_train = X_dev.iloc[
    :punto_validacion
].copy()

y_train = y_dev.iloc[
    :punto_validacion
].copy()

X_val = X_dev.iloc[
    punto_validacion:
].copy()

y_val = y_dev.iloc[
    punto_validacion:
].copy()

fecha_val_inicio = df.iloc[
    punto_validacion
]["fecha"]

fecha_val_fin = df.iloc[
    punto_test - 1
]["fecha"]

print(
    "\nENTRENAMIENTO:"
)

print(
    f"Registros: {len(X_train)}"
)

print(
    f"Periodo: "
    f"{df.iloc[0]['fecha'].date()} "
    f"→ "
    f"{df.iloc[punto_validacion - 1]['fecha'].date()}"
)

print(
    "\nVALIDACIÓN:"
)

print(
    f"Registros: {len(X_val)}"
)

print(
    f"Periodo: "
    f"{fecha_val_inicio.date()} "
    f"→ "
    f"{fecha_val_fin.date()}"
)


# ============================================================
# 15. CALCULAR PESOS DE ENTRENAMIENTO
# ============================================================

print(
    "\n[6/8] Calculando pesos continuos..."
)

pesos_train = crear_pesos_demanda(
    y_train
)

print(
    f"Alpha       : {PESO_ALPHA}"
)

print(
    f"Umbral      : {PESO_UMBRAL}"
)

print(
    f"Peso máximo : {PESO_MAXIMO}"
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


# ============================================================
# 16. ENTRENAMIENTO DE VALIDACIÓN
# ============================================================

print(
    "\nEntrenando modelo sobre entrenamiento..."
)

modelo_validacion = crear_modelo()

modelo_validacion.fit(
    X_train,
    y_train,
    sample_weight=pesos_train,
)


# ============================================================
# 17. EVALUAR VALIDACIÓN
# ============================================================

pred_val = modelo_validacion.predict(
    X_val
)

pred_val = np.maximum(
    pred_val,
    0
)

mae_val = mean_absolute_error(
    y_val,
    pred_val
)

rmse_val = np.sqrt(
    mean_squared_error(
        y_val,
        pred_val
    )
)

r2_val = r2_score(
    y_val,
    pred_val
)

print(
    "\n" + "=" * 70
)

print(
    " RESULTADO VALIDACIÓN"
)

print(
    "=" * 70
)

print(
    f"MAE  : {mae_val:.4f}"
)

print(
    f"RMSE : {rmse_val:.4f}"
)

print(
    f"R²   : {r2_val:.4f}"
)


# ============================================================
# 18. EVALUAR PICOS EN VALIDACIÓN
# ============================================================

y_val_array = y_val.to_numpy()

mascara_picos_val = (
    y_val_array >= 20
)

if mascara_picos_val.sum() > 0:

    mae_picos_val = mean_absolute_error(
        y_val_array[
            mascara_picos_val
        ],
        pred_val[
            mascara_picos_val
        ]
    )

    rmse_picos_val = np.sqrt(
        mean_squared_error(
            y_val_array[
                mascara_picos_val
            ],
            pred_val[
                mascara_picos_val
            ]
        )
    )

else:

    mae_picos_val = np.nan
    rmse_picos_val = np.nan

print(
    f"MAE picos >=20 : "
    f"{mae_picos_val:.4f}"
)

print(
    f"RMSE picos >=20: "
    f"{rmse_picos_val:.4f}"
)


# ============================================================
# 19. ENTRENAMIENTO FINAL
# ============================================================

print(
    "\n[7/8] Entrenando modelo final..."
)

# El modelo final utiliza todo el 80% de desarrollo.

pesos_desarrollo = crear_pesos_demanda(
    y_dev
)

modelo_final = crear_modelo()

modelo_final.fit(
    X_dev,
    y_dev,
    sample_weight=pesos_desarrollo,
)

print(
    "Modelo final entrenado correctamente."
)


# ============================================================
# 20. EVALUACIÓN FINAL
# ============================================================

print(
    "\n[8/8] Evaluando con datos de prueba..."
)

predicciones = modelo_final.predict(
    X_test
)

predicciones = np.maximum(
    predicciones,
    0
)

mae = mean_absolute_error(
    y_test,
    predicciones
)

rmse = np.sqrt(
    mean_squared_error(
        y_test,
        predicciones
    )
)

r2 = r2_score(
    y_test,
    predicciones
)


# ============================================================
# 21. RESULTADOS FINALES
# ============================================================

print(
    "\n" + "=" * 70
)

print(
    " RESULTADOS FINALES DEL NUEVO MODELO"
)

print(
    "=" * 70
)

print(
    f"MAE  : {mae:.4f}"
)

print(
    f"RMSE : {rmse:.4f}"
)

print(
    f"R²   : {r2:.4f}"
)


# ============================================================
# 22. ANÁLISIS DE PICOS
# ============================================================

y_test_array = y_test.to_numpy()

mascara_picos = (
    y_test_array >= 20
)

cantidad_picos = int(
    mascara_picos.sum()
)

if cantidad_picos > 0:

    mae_picos = mean_absolute_error(
        y_test_array[
            mascara_picos
        ],
        predicciones[
            mascara_picos
        ]
    )

    rmse_picos = np.sqrt(
        mean_squared_error(
            y_test_array[
                mascara_picos
            ],
            predicciones[
                mascara_picos
            ]
        )
    )

else:

    mae_picos = np.nan
    rmse_picos = np.nan

print(
    "\n" + "-" * 70
)

print(
    " ANÁLISIS DE DEMANDA ALTA"
)

print(
    "-" * 70
)

print(
    f"Registros con demanda >=20: "
    f"{cantidad_picos}"
)

print(
    f"MAE picos : "
    f"{mae_picos:.4f}"
)

print(
    f"RMSE picos: "
    f"{rmse_picos:.4f}"
)


# ============================================================
# 23. IMPORTANCIA DE VARIABLES
# ============================================================

print(
    "\n" + "-" * 70
)

print(
    " IMPORTANCIA DE VARIABLES"
)

print(
    "-" * 70
)

importancias = pd.DataFrame({
    "variable": FEATURES,
    "importancia": modelo_final.feature_importances_,
})

importancias = importancias.sort_values(
    "importancia",
    ascending=False
)

for _, fila in importancias.iterrows():

    print(
        f"{fila['variable']:25} "
        f"{fila['importancia']:.4f}"
    )


# ============================================================
# 24. EJEMPLOS DE PREDICCIÓN
# ============================================================

print(
    "\n" + "-" * 70
)

print(
    " EJEMPLOS DE PREDICCIÓN"
)

print(
    "-" * 70
)

ejemplos = pd.DataFrame({
    "fecha": df.iloc[
        punto_test:
    ]["fecha"].dt.strftime(
        "%Y-%m-%d"
    ).values,

    "producto_id": df.iloc[
        punto_test:
    ]["producto_id"].values,

    "real": y_test.values,

    "predicho": predicciones,
})

ejemplos["error"] = (
    ejemplos["real"]
    - ejemplos["predicho"]
).abs()

print(
    ejemplos.head(10).to_string(
        index=False
    )
)


# ============================================================
# 25. COMPARAR CONTRA MODELO ACTUAL
# ============================================================

MAE_MODELO_ACTUAL = 4.252990156763385
RMSE_MODELO_ACTUAL = 5.822283612757488
R2_MODELO_ACTUAL = 0.24703916152859473

mejora_mae = (
    (
        MAE_MODELO_ACTUAL
        - mae
    )
    / MAE_MODELO_ACTUAL
) * 100

mejora_rmse = (
    (
        RMSE_MODELO_ACTUAL
        - rmse
    )
    / RMSE_MODELO_ACTUAL
) * 100

cambio_r2 = (
    r2
    - R2_MODELO_ACTUAL
)

mejora_picos = np.nan

if cantidad_picos > 0:

    MAE_PICOS_ACTUAL = 13.585647

    mejora_picos = (
        (
            MAE_PICOS_ACTUAL
            - mae_picos
        )
        / MAE_PICOS_ACTUAL
    ) * 100


print(
    "\n" + "=" * 70
)

print(
    " COMPARACIÓN CONTRA MODELO ACTUAL"
)

print(
    "=" * 70
)

print(
    f"MAE actual      : "
    f"{MAE_MODELO_ACTUAL:.4f}"
)

print(
    f"MAE nuevo       : "
    f"{mae:.4f}"
)

print(
    f"Mejora MAE      : "
    f"{mejora_mae:+.2f}%"
)

print()

print(
    f"RMSE actual     : "
    f"{RMSE_MODELO_ACTUAL:.4f}"
)

print(
    f"RMSE nuevo      : "
    f"{rmse:.4f}"
)

print(
    f"Mejora RMSE     : "
    f"{mejora_rmse:+.2f}%"
)

print()

print(
    f"R² actual       : "
    f"{R2_MODELO_ACTUAL:.4f}"
)

print(
    f"R² nuevo        : "
    f"{r2:.4f}"
)

print(
    f"Cambio R²       : "
    f"{cambio_r2:+.4f}"
)

print()

if not np.isnan(
    mejora_picos
):

    print(
        f"Mejora MAE picos: "
        f"{mejora_picos:+.2f}%"
    )


# ============================================================
# 26. GUARDAR MODELO
# ============================================================

print(
    "\nGuardando modelo..."
)

os.makedirs(
    MODEL_DIR,
    exist_ok=True
)

paquete_modelo = {
    "modelo": modelo_final,

    "features": FEATURES,

    "mae": float(mae),

    "rmse": float(rmse),

    "r2": float(r2),

    "fecha_test_inicio": str(
        fecha_test_inicio.date()
    ),

    "fecha_dataset_inicio": str(
        fecha_inicio_dataset.date()
    ),

    "fecha_dataset_fin": str(
        fecha_fin_dataset.date()
    ),

    "registros_entrenamiento": int(
        len(X_dev)
    ),

    "registros_prueba": int(
        len(X_test)
    ),

    "mejores_parametros": {
        "n_estimators": MODELO_PARAMETROS[
            "n_estimators"
        ],

        "max_depth": MODELO_PARAMETROS[
            "max_depth"
        ],

        "min_samples_leaf": MODELO_PARAMETROS[
            "min_samples_leaf"
        ],

        "max_features": MODELO_PARAMETROS[
            "max_features"
        ],

        "random_state": MODELO_PARAMETROS[
            "random_state"
        ],
    },

    "metodo_entrenamiento":
        "RandomForest con pesos continuos",

    "peso_alpha":
        float(PESO_ALPHA),

    "peso_umbral":
        float(PESO_UMBRAL),

    "peso_maximo":
        float(PESO_MAXIMO),

    "mae_validacion":
        float(mae_val),

    "rmse_validacion":
        float(rmse_val),

    "r2_validacion":
        float(r2_val),

    "mae_picos_test":
        float(mae_picos)
        if not np.isnan(mae_picos)
        else None,

    "rmse_picos_test":
        float(rmse_picos)
        if not np.isnan(rmse_picos)
        else None,

    "version_modelo":
        "V3-pesos-continuos",
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


# ============================================================
# 27. RESUMEN FINAL
# ============================================================

print(
    "\n" + "=" * 70
)

print(
    " ENTRENAMIENTO FINALIZADO"
)

print(
    "=" * 70
)

print(
    f"Dataset               : "
    f"{len(df)} registros"
)

print(
    f"Entrenamiento         : "
    f"{len(X_dev)} registros"
)

print(
    f"Prueba                : "
    f"{len(X_test)} registros"
)

print(
    f"MAE final             : "
    f"{mae:.4f}"
)

print(
    f"RMSE final            : "
    f"{rmse:.4f}"
)

print(
    f"R² final              : "
    f"{r2:.4f}"
)

print(
    f"MAE validación        : "
    f"{mae_val:.4f}"
)

print(
    f"MAE picos >=20        : "
    f"{mae_picos:.4f}"
)

print(
    f"Pesos continuos       : "
    f"alpha={PESO_ALPHA}, "
    f"máximo={PESO_MAXIMO}"
)

print(
    f"Modelo                : "
    f"Random Forest 400/20/2/sqrt"
)

print(
    f"Archivo               : "
    f"{MODEL_PATH}"
)

print(
    "=" * 70
)