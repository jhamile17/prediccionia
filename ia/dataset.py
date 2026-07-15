import pandas as pd
from sqlalchemy import create_engine
from config import Config


class DatasetIA:

    @staticmethod
    def obtener_dataset():

        engine = create_engine(
            f"mysql+pymysql://{Config.DB_USER}:{Config.DB_PASSWORD}@{Config.DB_HOST}:{Config.DB_PORT}/{Config.DB_NAME}"
        )

        consulta = """
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
        """

        df = pd.read_sql(consulta, engine)

        return df

    @staticmethod
    def preparar_dataset():

        df = DatasetIA.obtener_dataset()

        print("\n=== DATOS ORIGINALES ===")
        print(df.head())

        df["fecha"] = pd.to_datetime(
            df["fecha"],
            format="%Y-%m-%d",
            errors="coerce"
        )

        # Verificar si hay fechas inválidas
        if df["fecha"].isna().any():
            print("\nHay fechas inválidas:")
            print(df[df["fecha"].isna()])
            raise ValueError("Existen fechas inválidas en la base de datos.")

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

        return dataset