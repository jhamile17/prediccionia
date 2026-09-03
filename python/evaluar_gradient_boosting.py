import pandas as pd
import numpy as np

from sklearn.ensemble import HistGradientBoostingRegressor
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

TARGET = "demanda"


CONFIGURACIONES = [
    {
        "nombre": "HGB-100-depthNone-leaf20-lr0.05",
        "max_iter": 100,
        "max_leaf_nodes": 31,
        "learning_rate": 0.05,
        "min_samples_leaf": 20,
        "l2_regularization": 0.0,
    },
    {
        "nombre": "HGB-200-depthNone-leaf20-lr0.05",
        "max_iter": 200,
        "max_leaf_nodes": 31,
        "learning_rate": 0.05,
        "min_samples_leaf": 20,
        "l2_regularization": 0.0,
    },
    {
        "nombre": "HGB-300-depthNone-leaf20-lr0.03",
        "max_iter": 300,
        "max_leaf_nodes": 31,
        "learning_rate": 0.03,
        "min_samples_leaf": 20,
        "l2_regularization": 0.0,
    },
    {
        "nombre": "HGB-200-depthNone-leaf10-lr0.05",
        "max_iter": 200,
        "max_leaf_nodes": 15,
        "learning_rate": 0.05,
        "min_samples_leaf": 10,
        "l2_regularization": 0.0,
    },
    {
        "nombre": "HGB-200-depthNone-leaf30-lr0.05",
        "max_iter": 200,
        "max_leaf_nodes": 31,
        "learning_rate": 0.05,
        "min_samples_leaf": 30,
        "l2_regularization": 0.0,
    },
    {
        "nombre": "HGB-300-depthNone-leaf20-lr0.05-l2",
        "max_iter": 300,
        "max_leaf_nodes": 31,
        "learning_rate": 0.05,
        "min_samples_leaf": 20,
        "l2_regularization": 1.0,
    },
]


def evaluar(config, desarrollo, test):

    modelo = HistGradientBoostingRegressor(
        max_iter=config["max_iter"],
        max_leaf_nodes=config["max_leaf_nodes"],
        learning_rate=config["learning_rate"],
        min_samples_leaf=config["min_samples_leaf"],
        l2_regularization=config["l2_regularization"],
        random_state=42,
    )

    modelo.fit(
        desarrollo[FEATURES],
        desarrollo[TARGET],
    )

    pred = modelo.predict(
        test[FEATURES]
    )

    pred = np.maximum(pred, 0)

    mae = mean_absolute_error(
        test[TARGET],
        pred,
    )

    rmse = np.sqrt(
        mean_squared_error(
            test[TARGET],
            pred,
        )
    )

    r2 = r2_score(
        test[TARGET],
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
    print("EXPERIMENTO: HISTOGRAM GRADIENT BOOSTING")
    print("=" * 70)

    df = pd.read_csv(
        DATASET_PATH
    )

    df["fecha"] = pd.to_datetime(
        df["fecha"]
    )

    df = df.sort_values(
        ["fecha", "producto_id"]
    ).reset_index(drop=True)

    indice_test = int(
        len(df) * 0.80
    )

    desarrollo = df.iloc[
        :indice_test
    ].copy()

    test = df.iloc[
        indice_test:
    ].copy()

    print()
    print(
        f"Dataset: {len(df)}"
    )

    print(
        f"Desarrollo: {len(desarrollo)}"
    )

    print(
        f"Test: {len(test)}"
    )

    print(
        f"Período test: "
        f"{test['fecha'].min().date()} "
        f"→ {test['fecha'].max().date()}"
    )

    resultados = []

    for i, config in enumerate(
        CONFIGURACIONES,
        start=1
    ):

        print()
        print(
            f"[{i}/{len(CONFIGURACIONES)}] "
            f"{config['nombre']}"
        )

        resultado = evaluar(
            config,
            desarrollo,
            test,
        )

        resultados.append(
            resultado
        )

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
    print("RANKING HISTGRADIENTBOOSTING")
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
        f"Configuración: "
        f"{mejor['configuracion']}"
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
    print("=" * 70)
    print("COMPARACIÓN CON RANDOM FOREST ACTUAL")
    print("=" * 70)

    mae_base = 4.252990156763385
    rmse_base = 5.822283612757488
    r2_base = 0.24703916152859473

    print(
        f"Random Forest MAE : {mae_base:.4f}"
    )

    print(
        f"Gradient Boosting MAE: "
        f"{mejor['mae']:.4f}"
    )

    mejora_mae = (
        (mae_base - mejor["mae"])
        / mae_base
    ) * 100

    mejora_rmse = (
        (rmse_base - mejor["rmse"])
        / rmse_base
    ) * 100

    cambio_r2 = (
        mejor["r2"] - r2_base
    )

    print()
    print(
        f"Mejora MAE : {mejora_mae:+.2f}%"
    )

    print(
        f"Mejora RMSE: {mejora_rmse:+.2f}%"
    )

    print(
        f"Cambio R²  : {cambio_r2:+.4f}"
    )

    print()

    if mejor["mae"] < mae_base:
        print(
            "✅ HistGradientBoosting supera "
            "al Random Forest actual."
        )
    else:
        print(
            "❌ Random Forest sigue siendo superior."
        )

    print()
    print(
        "modelo_demanda.pkl NO fue modificado."
    )


if __name__ == "__main__":
    main()