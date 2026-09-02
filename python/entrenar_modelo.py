import os
import pandas as pd
import joblib

from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import (
    mean_absolute_error,
    mean_squared_error,
    r2_score
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
# 2. CONFIGURACIÓN
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
    "es_dia_especial"
]

TARGET = "demanda"


# ============================================================
# 3. ENCABEZADO
# ============================================================

print("=" * 70)
print(" SISTEMA INTELIGENTE DE PREDICCIÓN DE DEMANDA")
print(" ENTRENAMIENTO V2")
print("=" * 70)


# ============================================================
# 4. CARGAR DATASET
# ============================================================

print("\n[1/7] Cargando dataset...")

if not os.path.exists(DATASET_PATH):
    raise FileNotFoundError(
        f"No se encontró el dataset:\n{DATASET_PATH}"
    )

df = pd.read_csv(DATASET_PATH)

print("Dataset cargado correctamente.")
print(f"Registros : {len(df)}")
print(f"Columnas  : {len(df.columns)}")


# ============================================================
# 5. VALIDAR COLUMNAS
# ============================================================

print("\n[2/7] Validando estructura del dataset...")

columnas_requeridas = [
    "fecha",
    TARGET
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

print("Todas las columnas requeridas existen.")


# ============================================================
# 6. PREPARAR FECHA
# ============================================================

df["fecha"] = pd.to_datetime(
    df["fecha"],
    errors="coerce"
)

if df["fecha"].isna().any():
    cantidad = df["fecha"].isna().sum()

    raise ValueError(
        f"Se encontraron {cantidad} fechas inválidas."
    )


# ============================================================
# 7. ORDEN CRONOLÓGICO
# ============================================================

print("\n[3/7] Ordenando datos cronológicamente...")

df = df.sort_values(
    ["fecha", "producto_id"]
).reset_index(drop=True)

print(
    f"Periodo del dataset: "
    f"{df['fecha'].min().date()} "
    f"→ "
    f"{df['fecha'].max().date()}"
)


# ============================================================
# 8. VALIDAR VALORES NULOS
# ============================================================

print("\nValidando valores faltantes...")

nulos = df[FEATURES + [TARGET]].isnull().sum()

nulos = nulos[nulos > 0]

if len(nulos) > 0:

    print("\nSe encontraron valores faltantes:")

    for columna, cantidad in nulos.items():
        print(
            f"  {columna}: {cantidad}"
        )

    raise ValueError(
        "El dataset contiene valores faltantes."
    )

print("No existen valores faltantes.")


# ============================================================
# 9. CREAR X E Y
# ============================================================

X = df[FEATURES].copy()
y = df[TARGET].copy()


# ============================================================
# 10. DIVISIÓN TEMPORAL
# ============================================================

print("\n[4/7] Dividiendo datos temporalmente...")

total = len(df)

# 80% será utilizado para desarrollo
# 20% quedará reservado como prueba final.

punto_test = int(total * 0.80)

X_dev = X.iloc[:punto_test].copy()
y_dev = y.iloc[:punto_test].copy()

X_test = X.iloc[punto_test:].copy()
y_test = y.iloc[punto_test:].copy()

fecha_test_inicio = df.iloc[punto_test]["fecha"]

print("\nDESARROLLO:")
print(
    f"Registros: {len(X_dev)}"
)

print(
    f"Periodo: "
    f"{df.iloc[0]['fecha'].date()} "
    f"→ "
    f"{df.iloc[punto_test - 1]['fecha'].date()}"
)

print("\nPRUEBA FINAL:")
print(
    f"Registros: {len(X_test)}"
)

print(
    f"Periodo: "
    f"{fecha_test_inicio.date()} "
    f"→ "
    f"{df.iloc[-1]['fecha'].date()}"
)


# ============================================================
# 11. DIVISIÓN ENTRENAMIENTO / VALIDACIÓN
# ============================================================

# Dentro del 80% de desarrollo:
#
# 80% → entrenamiento
# 20% → validación
#
# Esto permite seleccionar la mejor configuración
# sin utilizar todavía el conjunto de prueba final.

punto_validacion = int(len(X_dev) * 0.80)

X_train = X_dev.iloc[:punto_validacion].copy()
y_train = y_dev.iloc[:punto_validacion].copy()

X_val = X_dev.iloc[punto_validacion:].copy()
y_val = y_dev.iloc[punto_validacion:].copy()

print("\nENTRENAMIENTO:")
print(f"Registros: {len(X_train)}")

print("\nVALIDACIÓN:")
print(f"Registros: {len(X_val)}")


# ============================================================
# 12. CONFIGURACIONES A EVALUAR
# ============================================================

print("\n[5/7] Buscando la mejor configuración...")


configuraciones = [

    {
        "n_estimators": 200,
        "max_depth": 15,
        "min_samples_leaf": 2,
        "max_features": 1.0
    },

    {
        "n_estimators": 300,
        "max_depth": 15,
        "min_samples_leaf": 2,
        "max_features": 1.0
    },

    {
        "n_estimators": 300,
        "max_depth": 20,
        "min_samples_leaf": 2,
        "max_features": 1.0
    },

    {
        "n_estimators": 300,
        "max_depth": 12,
        "min_samples_leaf": 2,
        "max_features": 1.0
    },

    {
        "n_estimators": 300,
        "max_depth": 15,
        "min_samples_leaf": 3,
        "max_features": 1.0
    },

    {
        "n_estimators": 400,
        "max_depth": 20,
        "min_samples_leaf": 2,
        "max_features": "sqrt"
    }

]


resultados = []


# ============================================================
# 13. ENTRENAMIENTO DE CONFIGURACIONES
# ============================================================

for numero, parametros in enumerate(
    configuraciones,
    start=1
):

    print(
        f"\nConfiguración {numero}/"
        f"{len(configuraciones)}"
    )

    print(
        f"n_estimators      = "
        f"{parametros['n_estimators']}"
    )

    print(
        f"max_depth         = "
        f"{parametros['max_depth']}"
    )

    print(
        f"min_samples_leaf  = "
        f"{parametros['min_samples_leaf']}"
    )

    print(
        f"max_features      = "
        f"{parametros['max_features']}"
    )


    modelo_temp = RandomForestRegressor(
        n_estimators=parametros["n_estimators"],
        max_depth=parametros["max_depth"],
        min_samples_leaf=parametros["min_samples_leaf"],
        max_features=parametros["max_features"],
        random_state=42,
        n_jobs=-1
    )


    modelo_temp.fit(
        X_train,
        y_train
    )


    pred_val = modelo_temp.predict(
        X_val
    )


    mae_val = mean_absolute_error(
        y_val,
        pred_val
    )


    rmse_val = mean_squared_error(
        y_val,
        pred_val
    ) ** 0.5


    r2_val = r2_score(
        y_val,
        pred_val
    )


    print(
        f"MAE validación  : {mae_val:.4f}"
    )

    print(
        f"RMSE validación : {rmse_val:.4f}"
    )

    print(
        f"R² validación   : {r2_val:.4f}"
    )


    resultados.append({
        "parametros": parametros,
        "mae": mae_val,
        "rmse": rmse_val,
        "r2": r2_val
    })


# ============================================================
# 14. SELECCIONAR MEJOR MODELO
# ============================================================

mejor = min(
    resultados,
    key=lambda resultado: resultado["mae"]
)

mejores_parametros = mejor["parametros"]


print("\n" + "=" * 70)
print(" MEJOR CONFIGURACIÓN")
print("=" * 70)

print(
    f"MAE validación  : "
    f"{mejor['mae']:.4f}"
)

print(
    f"RMSE validación : "
    f"{mejor['rmse']:.4f}"
)

print(
    f"R² validación   : "
    f"{mejor['r2']:.4f}"
)

print("\nParámetros:")

for clave, valor in mejores_parametros.items():

    print(
        f"{clave:20}: {valor}"
    )


# ============================================================
# 15. ENTRENAMIENTO FINAL
# ============================================================

print("\n[6/7] Entrenando modelo final...")


# Utilizamos todo el 80% de desarrollo.
modelo_final = RandomForestRegressor(
    n_estimators=mejores_parametros["n_estimators"],
    max_depth=mejores_parametros["max_depth"],
    min_samples_leaf=mejores_parametros["min_samples_leaf"],
    max_features=mejores_parametros["max_features"],
    random_state=42,
    n_jobs=-1
)


modelo_final.fit(
    X_dev,
    y_dev
)


print(
    "Modelo final entrenado correctamente."
)


# ============================================================
# 16. EVALUACIÓN FINAL
# ============================================================

print("\n[7/7] Evaluando con datos de prueba...")


predicciones = modelo_final.predict(
    X_test
)


mae = mean_absolute_error(
    y_test,
    predicciones
)


rmse = mean_squared_error(
    y_test,
    predicciones
) ** 0.5


r2 = r2_score(
    y_test,
    predicciones
)


print("\n" + "=" * 70)
print(" RESULTADOS FINALES DEL MODELO")
print("=" * 70)

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
# 17. IMPORTANCIA DE VARIABLES
# ============================================================

print("\n" + "-" * 70)
print(" IMPORTANCIA DE VARIABLES")
print("-" * 70)


importancias = pd.DataFrame({
    "variable": FEATURES,
    "importancia": modelo_final.feature_importances_
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
# 18. EJEMPLOS DE PREDICCIÓN
# ============================================================

print("\n" + "-" * 70)
print(" EJEMPLOS DE PREDICCIÓN")
print("-" * 70)


ejemplos = pd.DataFrame({
    "real": y_test.values,
    "predicho": predicciones
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
# 19. GUARDAR MODELO
# ============================================================

print("\nGuardando modelo...")


os.makedirs(
    MODEL_DIR,
    exist_ok=True
)


joblib.dump(
    {
        "modelo": modelo_final,
        "features": FEATURES,
        "mae": mae,
        "rmse": rmse,
        "r2": r2,
        "fecha_test_inicio": str(
            fecha_test_inicio.date()
        ),
        "fecha_dataset_inicio": str(
            df["fecha"].min().date()
        ),
        "fecha_dataset_fin": str(
            df["fecha"].max().date()
        ),
        "registros_entrenamiento": len(X_dev),
        "registros_prueba": len(X_test),
        "mejores_parametros": mejores_parametros
    },
    MODEL_PATH
)


print("\nModelo guardado correctamente en:")

print(
    MODEL_PATH
)


# ============================================================
# 20. RESUMEN
# ============================================================

print("\n" + "=" * 70)
print(" ENTRENAMIENTO FINALIZADO")
print("=" * 70)

print(
    f"Dataset              : {len(df)} registros"
)

print(
    f"Entrenamiento        : {len(X_dev)} registros"
)

print(
    f"Prueba                : {len(X_test)} registros"
)

print(
    f"MAE final             : {mae:.4f}"
)

print(
    f"RMSE final            : {rmse:.4f}"
)

print(
    f"R² final              : {r2:.4f}"
)

print("=" * 70)