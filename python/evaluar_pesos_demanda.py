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
        "nombre": "Pesos suaves",
        "peso_normal": 1.0,
        "peso_alta": 1.5,
        "peso_pico": 2.0,
    },
    {
        "nombre": "Pesos medios",
        "peso_normal": 1.0,
        "peso_alta": 2.0,
        "peso_pico": 3.0,
    },
    {
        "nombre": "Pesos fuertes",
        "peso_normal": 1.0,
        "peso_alta": 2.0,
        "peso_pico": 4.0,
    },
    {
        "nombre": "Pesos muy fuertes",
        "peso_normal": 1.0,
        "peso_alta": 3.0,
        "peso_pico": 5.0,
    },
    {
        "nombre": "Pesos pico extremos",
        "peso_normal": 1.0,
        "peso_alta": 2.0,
        "peso_pico": 6.0,
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


def crear_pesos(y, configuracion):

    y = np.asarray(y)

    pesos = np.full(
        len(y),
        configuracion["peso_normal"],
        dtype=float,
    )

    mascara_alta = (
        (y >= 10)
        & (y < 20)
    )

    mascara_pico = y >= 20

    pesos[mascara_alta] = (
        configuracion["peso_alta"]
    )

    pesos[mascara_pico] = (
        configuracion["peso_pico"]
    )

    return pesos


def evaluar(configuracion, desarrollo, test):

    modelo = crear_modelo()

    pesos = crear_pesos(
        desarrollo["demanda"],
        configuracion,
    )

    modelo.fit(
        desarrollo[FEATURES],
        desarrollo["demanda"],
        sample_weight=pesos,
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
        "configuracion": configuracion["nombre"],
        "mae": mae,
        "rmse": rmse,
        "r2": r2,
        "mae_picos": mae_picos,
        "rmse_picos": rmse_picos,
    }


def main():

    print("=" * 70)
    print("EXPERIMENTO: PESOS PARA DEMANDA ALTA")
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

    picos = (
        test["demanda"] >= 20
    ).sum()

    print(
        f"Picos >=20: {picos}"
    )

    if picos != 107:
        print(
            "ADVERTENCIA: el número de picos "
            "no coincide con nuestra referencia."
        )

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

        print(
            f"Normal={configuracion['peso_normal']} | "
            f"Alta={configuracion['peso_alta']} | "
            f"Pico={configuracion['peso_pico']}"
        )

        resultado = evaluar(
            configuracion,
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

    # Referencia Random Forest actual
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
    print("MEJOR CONFIGURACIÓN")
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
        f"RF MAE: {rf_mae:.4f}"
    )

    print(
        f"Pesos MAE: {mejor['mae']:.4f}"
    )

    print(
        f"Mejora MAE: {mejora_mae:+.2f}%"
    )

    print()

    print(
        f"RF RMSE: {rf_rmse:.4f}"
    )

    print(
        f"Pesos RMSE: {mejor['rmse']:.4f}"
    )

    print(
        f"Mejora RMSE: {mejora_rmse:+.2f}%"
    )

    print()

    print(
        f"RF R²: {rf_r2:.4f}"
    )

    print(
        f"Pesos R²: {mejor['r2']:.4f}"
    )

    print(
        f"Cambio R²: {cambio_r2:+.4f}"
    )

    print()

    print(
        f"RF MAE picos: {rf_mae_picos:.4f}"
    )

    print(
        f"Pesos MAE picos: "
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
            "✅ Los pesos mejoran el MAE global."
        )

    else:

        print(
            "❌ Los pesos no mejoran el MAE global."
        )

    if mejor["mae_picos"] < rf_mae_picos:

        print(
            "✅ Los pesos mejoran los picos."
        )

    else:

        print(
            "⚠️ Los pesos no mejoran los picos."
        )

    print()
    print(
        "modelo_demanda.pkl NO fue modificado."
    )


if __name__ == "__main__":
    main()