import pandas as pd
import numpy as np

from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error


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


def main():

    print("=" * 70)
    print("EVALUACIÓN DEL MODELO POR PRODUCTO")
    print("=" * 70)

    df = pd.read_csv(DATASET_PATH)

    df["fecha"] = pd.to_datetime(df["fecha"])

    df = df.sort_values(
        ["fecha", "producto_id"]
    ).reset_index(drop=True)

    indice_test = int(len(df) * 0.80)

    desarrollo = df.iloc[:indice_test].copy()
    test = df.iloc[indice_test:].copy()

    print()
    print(
        f"Desarrollo: {len(desarrollo)}"
    )

    print(
        f"Test: {len(test)}"
    )

    print(
        f"Test: {test['fecha'].min().date()} "
        f"→ {test['fecha'].max().date()}"
    )

    modelo = RandomForestRegressor(
        n_estimators=400,
        max_depth=20,
        min_samples_leaf=2,
        max_features="sqrt",
        random_state=42,
        n_jobs=-1,
    )

    modelo.fit(
        desarrollo[FEATURES],
        desarrollo["demanda"],
    )

    pred = modelo.predict(
        test[FEATURES]
    )

    pred = np.maximum(pred, 0)

    test = test.copy()

    test["prediccion"] = pred

    resultados = []

    for producto_id, grupo in test.groupby(
        "producto_id",
        sort=True
    ):

        y_real = grupo["demanda"]
        y_pred = grupo["prediccion"]

        mae = mean_absolute_error(
            y_real,
            y_pred,
        )

        rmse = np.sqrt(
            mean_squared_error(
                y_real,
                y_pred,
            )
        )

        demanda_media = y_real.mean()

        error_relativo = (
            mae / demanda_media * 100
            if demanda_media > 0
            else 0
        )

        picos = y_real >= 20

        if picos.sum() > 0:

            mae_picos = mean_absolute_error(
                y_real[picos],
                y_pred[picos],
            )

        else:

            mae_picos = np.nan

        resultados.append({
            "producto_id": producto_id,
            "producto": grupo["producto"].iloc[0],
            "registros": len(grupo),
            "demanda_media": demanda_media,
            "mae": mae,
            "rmse": rmse,
            "error_relativo_pct": error_relativo,
            "picos_20+": int(picos.sum()),
            "mae_picos": mae_picos,
        })

    resultados_df = pd.DataFrame(
        resultados
    ).sort_values(
        "mae",
        ascending=False,
    )

    print()
    print("=" * 70)
    print("RESULTADOS POR PRODUCTO")
    print("=" * 70)

    print(
        resultados_df.to_string(
            index=False
        )
    )

    print()
    print("=" * 70)
    print("PRODUCTOS CON MAYOR ERROR")
    print("=" * 70)

    print(
        resultados_df[
            [
                "producto",
                "demanda_media",
                "mae",
                "error_relativo_pct",
                "picos_20+",
                "mae_picos",
            ]
        ]
        .head(5)
        .to_string(index=False)
    )

    print()
    print("=" * 70)
    print("PRODUCTOS CON MEJOR PRECISIÓN")
    print("=" * 70)

    print(
        resultados_df[
            [
                "producto",
                "demanda_media",
                "mae",
                "error_relativo_pct",
            ]
        ]
        .sort_values("mae")
        .head(5)
        .to_string(index=False)
    )


if __name__ == "__main__":
    main()