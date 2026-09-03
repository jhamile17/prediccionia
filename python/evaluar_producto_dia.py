import pandas as pd
import numpy as np

from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score


DATASET_PATH = "storage/app/datasets/dataset_demanda.csv"


FEATURES_BASE = [
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

FEATURES_MEJORADAS = FEATURES_BASE + [
    "producto_dia",
]


def crear_producto_dia(df):

    df = df.copy()

    df["producto_dia"] = (
        df["producto_id"] * 10
        + df["dia_semana"]
    )

    return df


def crear_modelo():

    return RandomForestRegressor(
        n_estimators=400,
        max_depth=20,
        min_samples_leaf=2,
        max_features="sqrt",
        random_state=42,
        n_jobs=-1,
    )


def evaluar(nombre, features, desarrollo, test):

    modelo = crear_modelo()

    modelo.fit(
        desarrollo[features],
        desarrollo["demanda"],
    )

    pred = modelo.predict(
        test[features]
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
        "modelo": nombre,
        "mae": mae,
        "rmse": rmse,
        "r2": r2,
        "pred": pred,
    }


def analizar_picos(nombre, test, pred):

    mascara = test["demanda"].to_numpy() >= 20

    if mascara.sum() == 0:
        return

    mae = mean_absolute_error(
        test.loc[mascara, "demanda"],
        pred[mascara],
    )

    rmse = np.sqrt(
        mean_squared_error(
            test.loc[mascara, "demanda"],
            pred[mascara],
        )
    )

    print()
    print(nombre)
    print(f"Picos >=20: {mascara.sum()}")
    print(f"MAE picos : {mae:.4f}")
    print(f"RMSE picos: {rmse:.4f}")


def main():

    print("=" * 70)
    print("EXPERIMENTO: PRODUCTO + DÍA DE SEMANA")
    print("=" * 70)

    df = pd.read_csv(DATASET_PATH)

    df["fecha"] = pd.to_datetime(
        df["fecha"]
    )

    # ORDEN TEMPORAL OFICIAL
    df = df.sort_values(
        ["fecha", "producto_id"]
    ).reset_index(drop=True)

    # CORTE TEMPORAL
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
    print(f"Dataset: {len(df)}")
    print(f"Desarrollo: {len(desarrollo)}")
    print(f"Test: {len(test)}")

    print(
        f"Test: {test['fecha'].min().date()} "
        f"→ {test['fecha'].max().date()}"
    )

    # Crear interacción
    df = crear_producto_dia(df)

    desarrollo = df.iloc[
        :indice_test
    ].copy()

    test = df.iloc[
        indice_test:
    ].copy()

    print()
    print(
        "Cantidad de combinaciones producto-día:",
        df["producto_dia"].nunique()
    )

    resultados = []

    # BASE
    base = evaluar(
        "MODELO BASE - 12 VARIABLES",
        FEATURES_BASE,
        desarrollo,
        test,
    )

    resultados.append(base)

    # MEJORADO
    mejorado = evaluar(
        "MODELO MEJORADO - + PRODUCTO_DÍA",
        FEATURES_MEJORADAS,
        desarrollo,
        test,
    )

    resultados.append(mejorado)

    # RESULTADOS
    print()
    print("=" * 70)
    print("RESULTADOS")
    print("=" * 70)

    for r in resultados:

        print()
        print(r["modelo"])
        print(f"MAE : {r['mae']:.4f}")
        print(f"RMSE: {r['rmse']:.4f}")
        print(f"R²  : {r['r2']:.4f}")

    mejora_mae = (
        (base["mae"] - mejorado["mae"])
        / base["mae"]
    ) * 100

    mejora_rmse = (
        (base["rmse"] - mejorado["rmse"])
        / base["rmse"]
    ) * 100

    cambio_r2 = (
        mejorado["r2"] -
        base["r2"]
    )

    print()
    print("=" * 70)
    print("COMPARACIÓN")
    print("=" * 70)

    print(
        f"Mejora MAE : {mejora_mae:+.2f}%"
    )

    print(
        f"Mejora RMSE: {mejora_rmse:+.2f}%"
    )

    print(
        f"Cambio R²  : {cambio_r2:+.4f}"
    )

    analizar_picos(
        "MODELO BASE",
        test,
        base["pred"],
    )

    analizar_picos(
        "MODELO PRODUCTO_DÍA",
        test,
        mejorado["pred"],
    )

    print()
    print("=" * 70)

    if mejorado["mae"] < base["mae"]:
        print(
            "✅ PRODUCTO_DÍA mejora el MAE."
        )
    else:
        print(
            "❌ PRODUCTO_DÍA no mejora el MAE."
        )

    print()
    print(
        "modelo_demanda.pkl NO fue modificado."
    )


if __name__ == "__main__":
    main()