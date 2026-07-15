from ia.dataset import DatasetIA

from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_absolute_error
import joblib


class EntrenadorIA:

    @staticmethod
    def entrenar():

        dataset = DatasetIA.preparar_dataset()

        X = dataset[
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

        y = dataset["demanda"]

        X_train, X_test, y_train, y_test = train_test_split(
            X,
            y,
            test_size=0.20,
            random_state=42
        )

        modelo = RandomForestRegressor(
            n_estimators=200,
            random_state=42
        )

        modelo.fit(X_train, y_train)

        predicciones = modelo.predict(X_test)

        mae = mean_absolute_error(y_test, predicciones)

        print("\n==============================")
        print("MODELO ENTRENADO")
        print("==============================")
        print(f"MAE: {mae:.2f}")

        joblib.dump(modelo, "ia/modelo.pkl")

        print("Modelo guardado en ia/modelo.pkl")