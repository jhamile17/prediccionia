from flask import Blueprint
from flask import render_template

from services.dashboard_service import DashboardService

dashboard_bp = Blueprint(
    "dashboard",
    __name__
)


@dashboard_bp.route("/dashboard")
def dashboard():

    return render_template(

        "dashboard.html",

        resumen=DashboardService.resumen(),

        predicciones=DashboardService.prediccion()

    )