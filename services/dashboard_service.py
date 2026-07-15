from models.dashboard_model import DashboardModel
from ia.prediccion import PrediccionIA


class DashboardService:

    @staticmethod
    def resumen():

        return DashboardModel.obtener_resumen()

    @staticmethod
    def prediccion():

        return PrediccionIA.predecir().to_dict(orient="records")