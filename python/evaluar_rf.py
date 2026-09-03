import pandas as pd
import numpy as np

from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score


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
        "nombre": "RF-200-depth15-leaf2",
        "n_estimators": 200,
        "max_depth": 15,
        "min_samples_leaf": 2,
        "max_features": 1.0,
    },
    {
        "nombre": "RF-300-depth15-leaf2",
        "n_estimators": 300,
        "max_depth": 15,
        "min_samples_leaf": 2,
        "max_features": 1.0,
    },
    {
        "nombre": "RF-300-depth20-leaf2",
        "n_estimators": 300,
        "max_depth": 20,
        "min_samples_leaf": 2,
        "max_features": 1.0,
    },
    {
        "nombre": "RF-300-depth12-leaf2",
        "n_estimators": 300,
        "max_depth": 12,
        "min_samples_leaf": 2,
        "max_features": 1.0,
    },
    {
        "nombre": "RF-300-depth15-leaf3",
        "n_estimators": 300,
        "max_depth": 15,
        "min_samples_leaf": 3,
        "max_features": 1.0,
    },
    {
        "nombre": "RF-400-depth20-leaf2-sqrt",
        "n_estimators": 400,
        "max_depth": 20,
        "min_samples_leaf": 2,
        "max_features": "sqrt",
    },
    {
        "nombre": "RF-500-depth20-leaf2",
        "n_estimators": 500,
        "max_depth": 20,
        "min_samples_leaf": 2,
        "max_features": 1.0,
    },
    {
        "nombre": "RF-500-depth25-leaf2",
        "n_estimators": 500,
        "max_depth": 25,
        "min_samples_leaf": 2,
        "max_features": 1.0,
    },
    {
        "nombre": "RF-400-depth15-leaf1",
        "n_estimators": 400,
        "max_depth": 15,
        "min_samples_leaf": 1,
        "max_features": 1.0,
    },
    {
        "nombre": "RF-400-depth20-leaf3",
        "n_estimators": 400,
        "max_depth": 20,
        "min_samples_leaf": 3,
        "max_features": 1.0,
    },
]


def evaluar(config, train, test):

    modelo = RandomForestRegressor(
        n_estimators=config["n_estimators"],
        max_depth=config["max_depth"],
        min_samples_leaf=config["min_samples_leaf"],
        max_features=config["max_features"],
        random_state=42,
        n_jobs=-1,
    )

    modelo.fit(
        train[FEATURES],
        train["demanda"],
    )

    pred = modelo.predict(
        test[FEATURES]
    )

    pred = np.maximum(pred, 0)

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

    return {
        "configuracion": config["nombre"],
        "mae": mae,
        "rmse": rmse,
        "r2": r2,
    }


def main():

    print("=" * 70)
    print("BÚSQUEDA DE HIPERPARÁMETROS - RANDOM FOREST")
    print("=" * 70)

    df = pd.read_csv(DATASET_PATH)

    df["fecha"] = pd.to_datetime(df["fecha"])

    df = df.sort_values(
        ["fecha", "producto_id"]
    ).reset_index(drop=True)

    # Corte temporal fijo
    indice_test = int(len(df) * 0.80)

    desarrollo = df.iloc[:indice_test].copy()
    test = df.iloc[indice_test:].copy()

    print()
    print(f"Dataset: {len(df)}")
    print(f"Desarrollo: {len(desarrollo)}")
    print(f"Test: {len(test)}")
    print(
        f"Test: {test['fecha'].min().date()} "
        f"→ {test['fecha'].max().date()}"
    )

    resultados = []

    for i, config in enumerate(CONFIGURACIONES, start=1):

        print()
        print(
            f"[{i}/{len(CONFIGURACIONES)}] "
            f"{config['nombre']}"
        )

        print(
            f"trees={config['n_estimators']} | "
            f"depth={config['max_depth']} | "
            f"leaf={config['min_samples_leaf']} | "
            f"features={config['max_features']}"
        )

        resultado = evaluar(
            config,
            desarrollo,
            test,
        )

        resultados.append(resultado)

        print(
            f"MAE={resultado['mae']:.4f} | "
            f"RMSE={resultado['rmse']:.4f} | "
            f"R²={resultado['r2']:.4f}"
        )

    resultados_df = pd.DataFrame(
        resultados
    ).sort_values(
        "mae"
    )

    print()
    print("=" * 70)
    print("RANKING FINAL")
    print("=" * 70)

    print(
        resultados_df.to_string(
            index=False
        )
    )

    mejor = resultados_df.iloc[0]

    print()
    print("=" * 70)
    print("MEJOR CONFIGURACIÓN")
    print("=" * 70)

    print(
        f"Configuración: {mejor['configuracion']}"
    )

    print(
        f"MAE : {mejor['mae']:.4f}"
    )

    print(
        f"RMSE: {mejor['rmse']:.4f}"
    )

    print(
        f"R²  : {mejor['r2']:.4f}"
    )

    print()
    print(
        "No se modificó modelo_demanda.pkl."
    )


if __name__ == "__main__":
    main()