import pandas as pd


DATASET_PATH = "storage/app/datasets/dataset_demanda.csv"


def main():

    print("=" * 70)
    print("ANÁLISIS DE PREDICTIBILIDAD DE PICOS")
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

    # --------------------------------------------------
    # CREAR ETIQUETA DE PICO
    # --------------------------------------------------

    test = test.copy()

    test["es_pico"] = (
        test["demanda"] >= 20
    ).astype(int)

    print()
    print("=" * 70)
    print("DISTRIBUCIÓN DE PICOS")
    print("=" * 70)

    print(
        test["es_pico"]
        .value_counts()
        .rename(
            index={
                0: "Normal",
                1: "Pico >=20",
            }
        )
        .to_string()
    )

    porcentaje = (
        test["es_pico"].mean()
        * 100
    )

    print()
    print(
        f"Porcentaje de picos: "
        f"{porcentaje:.2f}%"
    )

    # --------------------------------------------------
    # PICO POR DÍA DE SEMANA
    # --------------------------------------------------

    print()
    print("=" * 70)
    print("PROBABILIDAD DE PICO POR DÍA")
    print("=" * 70)

    por_dia = (
        test.groupby("dia_semana")
        .agg(
            registros=("demanda", "count"),
            picos=("es_pico", "sum"),
            demanda_media=("demanda", "mean"),
        )
    )

    por_dia["prob_pico_pct"] = (
        por_dia["picos"]
        / por_dia["registros"]
        * 100
    )

    print(
        por_dia.to_string()
    )

    # --------------------------------------------------
    # PICO POR PRODUCTO
    # --------------------------------------------------

    print()
    print("=" * 70)
    print("PROBABILIDAD DE PICO POR PRODUCTO")
    print("=" * 70)

    por_producto = (
        test.groupby(
            ["producto_id", "producto"]
        )
        .agg(
            registros=("demanda", "count"),
            picos=("es_pico", "sum"),
            demanda_media=("demanda", "mean"),
        )
    )

    por_producto["prob_pico_pct"] = (
        por_producto["picos"]
        / por_producto["registros"]
        * 100
    )

    print(
        por_producto
        .sort_values(
            "prob_pico_pct",
            ascending=False
        )
        .to_string()
    )

    # --------------------------------------------------
    # PRODUCTO + DÍA
    # --------------------------------------------------

    print()
    print("=" * 70)
    print("COMBINACIONES PRODUCTO + DÍA")
    print("=" * 70)

    por_combinacion = (
        test.groupby(
            [
                "producto_id",
                "producto",
                "dia_semana",
            ]
        )
        .agg(
            registros=("demanda", "count"),
            picos=("es_pico", "sum"),
            demanda_media=("demanda", "mean"),
        )
    )

    por_combinacion["prob_pico_pct"] = (
        por_combinacion["picos"]
        / por_combinacion["registros"]
        * 100
    )

    print(
        por_combinacion
        .sort_values(
            "prob_pico_pct",
            ascending=False
        )
        .head(20)
        .to_string()
    )

    # --------------------------------------------------
    # VARIABLES HISTÓRICAS EN LOS PICOS
    # --------------------------------------------------

    print()
    print("=" * 70)
    print("VARIABLES HISTÓRICAS: NORMAL VS PICO")
    print("=" * 70)

    columnas = [
        "demanda_anterior",
        "demanda_7_dias",
        "demanda_14_dias",
        "promedio_7_dias",
        "promedio_30_dias",
    ]

    resumen = (
        test.groupby("es_pico")[columnas]
        .mean()
        .rename(
            index={
                0: "Normal",
                1: "Pico >=20",
            }
        )
    )

    print(
        resumen.to_string()
    )

    # --------------------------------------------------
    # PICOS SEGÚN DEMANDA DE LA SEMANA ANTERIOR
    # --------------------------------------------------

    print()
    print("=" * 70)
    print("PICOS SEGÚN DEMANDA_7_DIAS")
    print("=" * 70)

    bins = [
        -1,
        4,
        9,
        14,
        19,
        999,
    ]

    labels = [
        "0-4",
        "5-9",
        "10-14",
        "15-19",
        "20+",
    ]

    test["rango_demanda_7"] = pd.cut(
        test["demanda_7_dias"],
        bins=bins,
        labels=labels,
    )

    por_rango = (
        test.groupby(
            "rango_demanda_7",
            observed=False
        )
        .agg(
            registros=("demanda", "count"),
            picos=("es_pico", "sum"),
        )
    )

    por_rango["prob_pico_pct"] = (
        por_rango["picos"]
        / por_rango["registros"]
        * 100
    )

    print(
        por_rango.to_string()
    )


if __name__ == "__main__":
    main()