import joblib
import pandas as pd

from ia.dataset import DatasetIA


class PrediccionIA:

    @staticmethod
    def predecir():

        # Cargar modelo entrenado
        modelo = joblib.load("ia/modelo.pkl")

        # Obtener dataset
        dataset = DatasetIA.preparar_dataset()

        # Último registro de cada producto
        ultimo = (
            dataset
            .sort_values(["id", "anio", "mes"])
            .groupby("id")
            .tail(1)
            .copy()
        )

        # Calcular siguiente mes
        ultimo["mes"] = ultimo["mes"] + 1

        # Si pasa de diciembre
        ultimo.loc[ultimo["mes"] == 13, "mes"] = 1
        ultimo.loc[ultimo["mes"] == 1, "anio"] += 1

        # Variables predictoras
        X = ultimo[
            [
                "precio",
                "stock",
                "stock_minimo",
                "anio",
                "mes",
                "ventas_mes_anterior",
                "promedio_3_meses"
            ]
        ]

        # Predicción
        ultimo["demanda_estimada"] = modelo.predict(X).round()

        # Reposición
        ultimo["reponer"] = (
            ultimo["demanda_estimada"] - ultimo["stock"]
        )

        ultimo["reponer"] = ultimo["reponer"].clip(lower=0)

        # Estado
        ultimo["estado"] = ultimo.apply(

            lambda fila:
                "🔴 Riesgo"
                if fila["stock"] < fila["demanda_estimada"]
                else "🟢 Stock suficiente",

            axis=1

        )

        return ultimo[
            [
                "nombre",
                "stock",
                "demanda_estimada",
                "reponer",
                "estado"
            ]
        ]