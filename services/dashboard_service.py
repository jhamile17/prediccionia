from models.dashboard_model import DashboardModel


class DashboardService:

    @staticmethod
    def resumen():

        return DashboardModel.obtener_resumen()