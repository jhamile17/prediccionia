import pandas as pd
import joblib

from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score


DATASET_PATH = "storage/app/datasets/dataset_demanda.csv"
MODEL_PATH = "python/modelos/modelo_demanda.pkl"


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


def main():

    print("=" * 60)
    print("EVALUACIÓN DEL MODELO ACTUAL")
    print("=" * 60)

    print("\nCargando dataset...")
    df = pd.read_csv(DATASET_PATH)

    df["fecha"] = pd.to_datetime(df["fecha"])

    df = df.sort_values(["fecha", "producto_id"]).reset_index(drop=True)

    print(f"Registros: {len(df)}")
    print(f"Fecha inicial: {df['fecha'].min().date()}")
    print(f"Fecha final: {df['fecha'].max().date()}")

    # Verificar variables
    faltantes = [col for col in FEATURES if col not in df.columns]

    if faltantes:
        raise ValueError(
            f"Faltan variables requeridas: {faltantes}"
        )

    # Mismo esquema temporal utilizado por el entrenamiento
    n = len(df)

    indice_test = int(n * 0.80)

    test = df.iloc[indice_test:].copy()

    print("\nDivisión temporal:")
    print(f"Desarrollo: {indice_test} registros")
    print(f"Test:       {len(test)} registros")

    print("\nTest:")
    print(f"Desde: {test['fecha'].min().date()}")
    print(f"Hasta: {test['fecha'].max().date()}")

    # Cargar modelo
    print("\nCargando modelo...")
    paquete = joblib.load(MODEL_PATH)

    if isinstance(paquete, dict) and "modelo" in paquete:
        modelo = paquete["modelo"]
    else:
        modelo = paquete

    X_test = test[FEATURES]
    y_test = test[TARGET]

    print("\nGenerando predicciones...")
    predicciones = modelo.predict(X_test)

    # Evitar predicciones negativas
    predicciones = predicciones.clip(min=0)

    mae = mean_absolute_error(y_test, predicciones)

    rmse = mean_squared_error(
        y_test,
        predicciones
    ) ** 0.5

    r2 = r2_score(y_test, predicciones)

    print("\n" + "=" * 60)
    print("RESULTADOS")
    print("=" * 60)

    print(f"MAE  : {mae:.4f}")
    print(f"RMSE : {rmse:.4f}")
    print(f"R²   : {r2:.4f}")

    print("\nInterpretación:")
    print(f"Error promedio aproximado: {mae:.2f} unidades")

    print("\nEvaluación terminada.")
    print("=" * 60)


if __name__ == "__main__":
    main()