import os
import pandas as pd

from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
import joblib


# ============================================================
# 1. RUTAS
# ============================================================

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

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
# 2. CARGAR DATASET
# ============================================================

print("=" * 60)
print("SISTEMA INTELIGENTE DE PREDICCIÓN DE DEMANDA")
print("=" * 60)

print("\n[1/6] Cargando dataset...")

df = pd.read_csv(DATASET_PATH)

print(f"Dataset cargado correctamente.")
print(f"Registros: {len(df)}")
print(f"Columnas: {len(df.columns)}")


# ============================================================
# 3. PREPARAR VARIABLES
# ============================================================

print("\n[2/6] Preparando variables...")

# Variables que utilizaremos para realizar la predicción
features = [
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

target = "demanda"


# Comprobamos que todas las columnas existan
columnas_faltantes = [
    columna for columna in features + [target]
    if columna not in df.columns
]

if columnas_faltantes:
    raise Exception(
        "Faltan las siguientes columnas: "
        + ", ".join(columnas_faltantes)
    )


X = df[features].copy()
y = df[target].copy()


print(f"Variables de entrada: {len(features)}")
print(f"Variable objetivo: {target}")


# ============================================================
# 4. DIVISIÓN TEMPORAL
# ============================================================

print("\n[3/6] Dividiendo datos de entrenamiento y prueba...")

# Ordenamos cronológicamente
df["fecha"] = pd.to_datetime(df["fecha"])

orden = df["fecha"].sort_values().index

X = X.loc[orden].reset_index(drop=True)
y = y.loc[orden].reset_index(drop=True)

# 80% para entrenamiento
# 20% para prueba
punto_corte = int(len(X) * 0.80)

X_train = X.iloc[:punto_corte]
X_test = X.iloc[punto_corte:]

y_train = y.iloc[:punto_corte]
y_test = y.iloc[punto_corte:]


print(f"Registros de entrenamiento: {len(X_train)}")
print(f"Registros de prueba: {len(X_test)}")


# ============================================================
# 5. ENTRENAR MODELO
# ============================================================

print("\n[4/6] Entrenando modelo Random Forest...")

modelo = RandomForestRegressor(
    n_estimators=200,
    max_depth=15,
    min_samples_leaf=2,
    random_state=42,
    n_jobs=-1
)

modelo.fit(X_train, y_train)

print("Modelo entrenado correctamente.")


# ============================================================
# 6. EVALUAR MODELO
# ============================================================

print("\n[5/6] Evaluando modelo...")

predicciones = modelo.predict(X_test)

mae = mean_absolute_error(y_test, predicciones)

rmse = mean_squared_error(
    y_test,
    predicciones
) ** 0.5

r2 = r2_score(y_test, predicciones)


print("\n" + "-" * 40)
print("RESULTADOS DEL MODELO")
print("-" * 40)

print(f"MAE  : {mae:.2f}")
print(f"RMSE : {rmse:.2f}")
print(f"R²   : {r2:.4f}")


# ============================================================
# 7. IMPORTANCIA DE VARIABLES
# ============================================================

print("\nImportancia de las variables:")

importancias = pd.DataFrame({
    "variable": features,
    "importancia": modelo.feature_importances_
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
# 8. GUARDAR MODELO
# ============================================================

print("\n[6/6] Guardando modelo...")

os.makedirs(MODEL_DIR, exist_ok=True)

joblib.dump(
    {
        "modelo": modelo,
        "features": features,
        "mae": mae,
        "rmse": rmse,
        "r2": r2
    },
    MODEL_PATH
)

print(f"\nModelo guardado en:")
print(MODEL_PATH)

print("\n" + "=" * 60)
print("ENTRENAMIENTO FINALIZADO CORRECTAMENTE")
print("=" * 60)