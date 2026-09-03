import os
import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score


# ============================================================
# CONFIGURACIÓN
# ============================================================

BASE_DIR = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

DATASET_PATH = os.path.join(
    BASE_DIR,
    "storage",
    "app",
    "datasets",
    "dataset_demanda.csv"
)

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

RF_PARAMS = {
    "n_estimators": 400,
    "max_depth": 20,
    "min_samples_leaf": 2,
    "max_features": "sqrt",
    "random_state": 42,
    "n_jobs": -1,
}

PESO_UMBRAL = 10.0


# ============================================================
# PESOS
# ============================================================

def crear_pesos(y, alpha, maximo):
    exceso = np.maximum(y - PESO_UMBRAL, 0)

    pesos = 1.0 + alpha * exceso

    return np.minimum(pesos, maximo)


# ============================================================
# MÉTRICAS
# ============================================================

def calcular_metricas(y_real, pred):
    mae = mean_absolute_error(y_real, pred)
    rmse = np.sqrt(mean_squared_error(y_real, pred))
    r2 = r2_score(y_real, pred)

    mascara_picos = y_real >= 20

    if mascara_picos.sum() > 0:
        mae_picos = mean_absolute_error(
            y_real[mascara_picos],
            pred[mascara_picos]
        )

        rmse_picos = np.sqrt(
            mean_squared_error(
                y_real[mascara_picos],
                pred[mascara_picos]
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
    }


# ============================================================
# ENTRENAR
# ============================================================

def entrenar(alpha, maximo, X_train, y_train):

    pesos = crear_pesos(
        y_train,
        alpha,
        maximo
    )

    modelo = RandomForestRegressor(
        **RF_PARAMS
    )

    modelo.fit(
        X_train,
        y_train,
        sample_weight=pesos
    )

    return modelo, pesos


# ============================================================
# PROGRAMA PRINCIPAL
# ============================================================

print("=" * 70)
print(" EXPERIMENTO V5 - PESOS PARA DEMANDA ALTA")
print("=" * 70)

print("\n[1/5] Cargando dataset...")

df = pd.read_csv(DATASET_PATH)

df["fecha"] = pd.to_datetime(df["fecha"])

df = df.sort_values(
    ["fecha", "producto_id"]
).reset_index(drop=True)

print(f"Registros: {len(df)}")
print(
    f"Periodo: {df['fecha'].min().date()} → "
    f"{df['fecha'].max().date()}"
)


# ============================================================
# VALIDACIÓN DE ESTRUCTURA
# ============================================================

faltantes = [
    columna
    for columna in FEATURES
    if columna not in df.columns
]

if faltantes:
    raise ValueError(
        f"Faltan columnas del modelo: {faltantes}"
    )


# ============================================================
# DIVISIÓN TEMPORAL
# ============================================================

print("\n[2/5] División temporal...")

n_total = len(df)

corte_test = int(n_total * 0.80)

desarrollo = df.iloc[:corte_test].copy()
test = df.iloc[corte_test:].copy()

corte_validacion = int(len(desarrollo) * 0.80)

train = desarrollo.iloc[:corte_validacion].copy()
validacion = desarrollo.iloc[corte_validacion:].copy()

print("\nTRAIN")
print(
    f"{train['fecha'].min().date()} → "
    f"{train['fecha'].max().date()}"
)
print(f"Registros: {len(train)}")

print("\nVALIDACIÓN")
print(
    f"{validacion['fecha'].min().date()} → "
    f"{validacion['fecha'].max().date()}"
)
print(f"Registros: {len(validacion)}")

print("\nTEST FINAL")
print(
    f"{test['fecha'].min().date()} → "
    f"{test['fecha'].max().date()}"
)
print(f"Registros: {len(test)}")


X_train = train[FEATURES]
y_train = train["demanda"]

X_valid = validacion[FEATURES]
y_valid = validacion["demanda"]

X_dev = desarrollo[FEATURES]
y_dev = desarrollo["demanda"]

X_test = test[FEATURES]
y_test = test["demanda"]


# ============================================================
# EXPERIMENTOS
# ============================================================

experimentos = [
    {
        "nombre": "A - V4 actual",
        "alpha": 0.02,
        "maximo": 2.0,
    },
    {
        "nombre": "B - Mayor peso",
        "alpha": 0.03,
        "maximo": 2.0,
    },
    {
        "nombre": "C - Mayor peso + máximo",
        "alpha": 0.04,
        "maximo": 2.5,
    },
]


# ============================================================
# VALIDACIÓN
# ============================================================

print("\n[3/5] Evaluando configuraciones sobre VALIDACIÓN...")

resultados_validacion = []

modelos_validacion = {}

for exp in experimentos:

    print("\n" + "-" * 70)
    print(exp["nombre"])
    print(
        f"alpha={exp['alpha']} | "
        f"máximo={exp['maximo']}"
    )

    modelo, pesos = entrenar(
        exp["alpha"],
        exp["maximo"],
        X_train,
        y_train
    )

    pred = modelo.predict(X_valid)

    metricas = calcular_metricas(
        y_valid.to_numpy(),
        pred
    )

    resultados_validacion.append({
        **exp,
        **metricas
    })

    modelos_validacion[exp["nombre"]] = modelo

    print(f"MAE       : {metricas['mae']:.4f}")
    print(f"RMSE      : {metricas['rmse']:.4f}")
    print(f"R²        : {metricas['r2']:.4f}")
    print(f"MAE picos : {metricas['mae_picos']:.4f}")

print("\n" + "=" * 70)
print(" RESULTADOS VALIDACIÓN")
print("=" * 70)

tabla_validacion = pd.DataFrame(resultados_validacion)

print(
    tabla_validacion[
        [
            "nombre",
            "alpha",
            "maximo",
            "mae",
            "rmse",
            "r2",
            "mae_picos",
        ]
    ]
    .round(4)
    .to_string(index=False)
)


# ============================================================
# SELECCIÓN
# ============================================================

mejor = min(
    resultados_validacion,
    key=lambda x: x["mae"]
)

print("\n" + "=" * 70)
print(" CONFIGURACIÓN SELECCIONADA")
print("=" * 70)

print(f"Nombre : {mejor['nombre']}")
print(f"Alpha  : {mejor['alpha']}")
print(f"Máximo : {mejor['maximo']}")
print(f"MAE val: {mejor['mae']:.4f}")


# ============================================================
# ENTRENAMIENTO FINAL
# ============================================================

print("\n[4/5] Entrenando configuración seleccionada sobre DESARROLLO...")

modelo_final, pesos_final = entrenar(
    mejor["alpha"],
    mejor["maximo"],
    X_dev,
    y_dev
)

pred_test = modelo_final.predict(X_test)

metricas_test = calcular_metricas(
    y_test.to_numpy(),
    pred_test
)


# ============================================================
# RESULTADO TEST
# ============================================================

print("\n" + "=" * 70)
print(" RESULTADO FINAL SOBRE TEST")
print("=" * 70)

print(f"MAE       : {metricas_test['mae']:.4f}")
print(f"RMSE      : {metricas_test['rmse']:.4f}")
print(f"R²        : {metricas_test['r2']:.4f}")
print(f"MAE picos : {metricas_test['mae_picos']:.4f}")
print(f"RMSE picos: {metricas_test['rmse_picos']:.4f}")


# ============================================================
# COMPARACIÓN
# ============================================================

print("\n" + "=" * 70)
print(" COMPARACIÓN V4 VS CANDIDATO")
print("=" * 70)

V4_MAE = 1.0969
V4_RMSE = 1.5715
V4_R2 = 0.8592
V4_MAE_PICOS = 3.7205

print(f"V4 MAE        : {V4_MAE:.4f}")
print(f"Candidato MAE : {metricas_test['mae']:.4f}")

mejora_mae = ((V4_MAE - metricas_test["mae"]) / V4_MAE) * 100

print(f"Mejora MAE    : {mejora_mae:+.2f}%")

print()

print(f"V4 RMSE        : {V4_RMSE:.4f}")
print(f"Candidato RMSE : {metricas_test['rmse']:.4f}")

mejora_rmse = ((V4_RMSE - metricas_test["rmse"]) / V4_RMSE) * 100

print(f"Mejora RMSE    : {mejora_rmse:+.2f}%")

print()

print(f"V4 R²        : {V4_R2:.4f}")
print(f"Candidato R² : {metricas_test['r2']:.4f}")

print(
    f"Cambio R²   : "
    f"{metricas_test['r2'] - V4_R2:+.4f}"
)

print()

print(f"V4 MAE picos        : {V4_MAE_PICOS:.4f}")
print(f"Candidato MAE picos : {metricas_test['mae_picos']:.4f}")

mejora_picos = (
    (V4_MAE_PICOS - metricas_test["mae_picos"])
    / V4_MAE_PICOS
) * 100

print(f"Mejora picos        : {mejora_picos:+.2f}%")


# ============================================================
# CONCLUSIÓN
# ============================================================

print("\n" + "=" * 70)
print(" CONCLUSIÓN")
print("=" * 70)

if (
    metricas_test["mae"] < V4_MAE
    and metricas_test["mae_picos"] < V4_MAE_PICOS
):
    print("✅ El candidato mejora V4 en MAE global y MAE de picos.")
elif metricas_test["mae"] < V4_MAE:
    print("⚠️ Mejora MAE global, pero no mejora simultáneamente los picos.")
elif metricas_test["mae_picos"] < V4_MAE_PICOS:
    print("⚠️ Mejora los picos, pero no mejora el MAE global.")
else:
    print("❌ No mejora V4.")

print("\n[5/5] Experimento terminado.")
print("El modelo de producción NO fue modificado.")
print("=" * 70)