import pandas as pd
import numpy as np

from sklearn.ensemble import ExtraTreesRegressor
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
        "nombre": "ET-300-depthNone-leaf2-sqrt",
        "n_estimators": 300,
        "max_depth": None,
        "min_samples_leaf": 2,
        "max_features": "sqrt",
    },
    {
        "nombre": "ET-400-depthNone-leaf2-sqrt",
        "n_estimators": 400,
        "max_depth": None,
        "min_samples_leaf": 2,
        "max_features": "sqrt",
    },
    {
        "nombre": "ET-500-depthNone-leaf2-sqrt",
        "n_estimators": 500,
        "max_depth": None,
        "min_samples_leaf": 2,
        "max_features": "sqrt",
    },
    {
        "nombre": "ET-400-depth20-leaf2-sqrt",
        "n_estimators": 400,
        "max_depth": 20,
        "min_samples_leaf": 2,
        "max_features": "sqrt",
    },
    {
        "nombre": "ET-400-depth30-leaf2-sqrt",
        "n_estimators": 400,
        "max_depth": 30,
        "min_samples_leaf": 2,
        "max_features": "sqrt",
    },
    {
        "nombre": "ET-400-depthNone-leaf3-sqrt",
        "n_estimators": 400,
        "max_depth": None,
        "min_samples_leaf": 3,
        "max_features": "sqrt",
    },
    {
        "nombre": "ET-400-depthNone-leaf2-all",
        "n_estimators": 400,
        "max_depth": None,
        "min_samples_leaf": 2,
        "max_features": 1.0,
    },
]


def crear_modelo(config):

    return ExtraTreesRegressor(
        n_estimators=config["n_estimators"],
        max_depth=config["max_depth"],
        min_samples_leaf=config["min_samples_leaf"],
        max_features=config["max_features"],
        random_state=42,
        n_jobs=-1,
    )


def evaluar(config, desarrollo, test):

    modelo = crear_modelo(config)

    modelo.fit(
        desarrollo[FEATURES],
        desarrollo["demanda"],
    )

    pred = modelo.predict(
        test[FEATURES]
    )

    pred = np.maximum(
        pred,
        0,
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

    # Picos >= 20
    mascara_picos = (
        test["demanda"].to_numpy()
        >= 20
    )

    if mascara_picos.sum() > 0:

        mae_picos = mean_absolute_error(
            test.loc[
                mascara_picos,
                "demanda",
            ],
            pred[
                mascara_picos
            ],
        )

        rmse_picos = np.sqrt(
            mean_squared_error(
                test.loc[
                    mascara_picos,
                    "demanda",
                ],
                pred[
                    mascara_picos
                ],
            )
        )

    else:

        mae_picos = np.nan
        rmse_picos = np.nan

    return {
        "configuracion": config["nombre"],
        "mae": mae,
        "rmse": rmse,
        "r2": r2,
        "mae_picos": mae_picos,
        "rmse_picos": rmse_picos,
    }


def main():

    print("=" * 70)
    print("EXPERIMENTO: EXTRA TREES")
    print("=" * 70)

    df = pd.read_csv(
        DATASET_PATH
    )

    df["fecha"] = pd.to_datetime(
        df["fecha"]
    )

    # IMPORTANTE:
    # mantener el orden cronológico global.
    df = df.sort_values(
        [
            "fecha",
            "producto_id",
        ]
    ).reset_index(
        drop=True
    )

    # ==================================================
    # CORTE TEMPORAL OFICIAL
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
        f"Test: "
        f"{test['fecha'].min().date()} "
        f"→ "
        f"{test['fecha'].max().date()}"
    )

    # Comprobación importante
    picos = (
        test["demanda"]
        >= 20
    ).sum()

    print(
        f"Picos >=20 en test: {picos}"
    )

    if picos != 107:

        print(
            "ADVERTENCIA: el número de picos "
            "no coincide con nuestra referencia."
        )

    resultados = []

    # ==================================================
    # EXPERIMENTOS
    # ==================================================

    for i, config in enumerate(
        CONFIGURACIONES,
        start=1,
    ):

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

        resultados.append(
            resultado
        )

        print(
            f"MAE={resultado['mae']:.4f} | "
            f"RMSE={resultado['rmse']:.4f} | "
            f"R²={resultado['r2']:.4f}"
        )

        print(
            f"MAE picos={resultado['mae_picos']:.4f} | "
            f"RMSE picos={resultado['rmse_picos']:.4f}"
        )

    # ==================================================
    # RANKING
    # ==================================================

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

    # ==================================================
    # COMPARACIÓN CON RANDOM FOREST ACTUAL
    # ==================================================

    rf_mae = 4.252990156763385
    rf_rmse = 5.822283612757488
    rf_r2 = 0.24703916152859473
    rf_mae_picos = 13.585647

    mejora_mae = (
        (
            rf_mae
            - mejor["mae"]
        )
        / rf_mae
    ) * 100

    mejora_rmse = (
        (
            rf_rmse
            - mejor["rmse"]
        )
        / rf_rmse
    ) * 100

    cambio_r2 = (
        mejor["r2"]
        - rf_r2
    )

    mejora_picos = (
        (
            rf_mae_picos
            - mejor["mae_picos"]
        )
        / rf_mae_picos
    ) * 100

    print()
    print("=" * 70)
    print("MEJOR EXTRA TREES")
    print("=" * 70)

    print(
        f"Configuración: "
        f"{mejor['configuracion']}"
    )

    print(
        f"MAE : "
        f"{mejor['mae']:.4f}"
    )

    print(
        f"RMSE: "
        f"{mejor['rmse']:.4f}"
    )

    print(
        f"R²  : "
        f"{mejor['r2']:.4f}"
    )

    print(
        f"MAE picos: "
        f"{mejor['mae_picos']:.4f}"
    )

    print()
    print("=" * 70)
    print("COMPARACIÓN CONTRA RANDOM FOREST")
    print("=" * 70)

    print(
        f"RF MAE: "
        f"{rf_mae:.4f}"
    )

    print(
        f"ET MAE: "
        f"{mejor['mae']:.4f}"
    )

    print(
        f"Mejora MAE: "
        f"{mejora_mae:+.2f}%"
    )

    print()

    print(
        f"RF RMSE: "
        f"{rf_rmse:.4f}"
    )

    print(
        f"ET RMSE: "
        f"{mejor['rmse']:.4f}"
    )

    print(
        f"Mejora RMSE: "
        f"{mejora_rmse:+.2f}%"
    )

    print()

    print(
        f"RF R²: "
        f"{rf_r2:.4f}"
    )

    print(
        f"ET R²: "
        f"{mejor['r2']:.4f}"
    )

    print(
        f"Cambio R²: "
        f"{cambio_r2:+.4f}"
    )

    print()

    print(
        f"RF MAE picos: "
        f"{rf_mae_picos:.4f}"
    )

    print(
        f"ET MAE picos: "
        f"{mejor['mae_picos']:.4f}"
    )

    print(
        f"Mejora picos: "
        f"{mejora_picos:+.2f}%"
    )

    print()
    print("=" * 70)

    if mejor["mae"] < rf_mae:

        print(
            "✅ Extra Trees supera al "
            "Random Forest en MAE."
        )

    else:

        print(
            "❌ Random Forest sigue siendo "
            "mejor en MAE."
        )

    print()
    print(
        "modelo_demanda.pkl NO fue modificado."
    )


if __name__ == "__main__":
    main()