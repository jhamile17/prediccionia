import pandas as pd
from database.db import get_connection


class DatasetIA:

    @staticmethod
    def obtener_dataset():

        conexion = get_connection()

        try:

            with conexion.cursor() as cursor:

                cursor.execute("""
                    SELECT
                        p.id,
                        p.nombre,
                        p.precio,
                        p.stock,
                        p.stock_minimo,
                        v.cantidad,
                        v.fecha
                    FROM ventas v
                    INNER JOIN productos p
                        ON p.id = v.producto_id
                    ORDER BY v.fecha
                """)

                datos = cursor.fetchall()

            df = pd.DataFrame(datos)

            return df

        finally:

            conexion.close()

    @staticmethod
    def preparar_dataset():

        df = DatasetIA.obtener_dataset()

        print("\n=== DATOS ORIGINALES ===")
        print(df.head())

        df["fecha"] = pd.to_datetime(
            df["fecha"],
            format="%Y-%m-%d"
        )

        df["anio"] = df["fecha"].dt.year
        df["mes"] = df["fecha"].dt.month

        dataset = (
            df.groupby(
                [
                    "id",
                    "nombre",
                    "anio",
                    "mes",
                    "precio",
                    "stock",
                    "stock_minimo"
                ]
            )["cantidad"]
            .sum()
            .reset_index()
        )

        dataset.rename(
            columns={"cantidad": "demanda"},
            inplace=True
        )

        dataset = dataset.sort_values(
            ["id", "anio", "mes"]
        )

        dataset["ventas_mes_anterior"] = (
            dataset.groupby("id")["demanda"]
            .shift(1)
            .fillna(0)
        )

        dataset["promedio_3_meses"] = (
            dataset.groupby("id")["demanda"]
            .rolling(
                window=3,
                min_periods=1
            )
            .mean()
            .reset_index(level=0, drop=True)
        )

        return dataset