from flask import Blueprint
from flask import render_template
from flask import request
from flask import redirect
from flask import url_for

from services.venta_service import VentaService
from services.producto_service import ProductoService

venta_bp = Blueprint(
    "ventas",
    __name__
)


@venta_bp.route("/ventas")
def ventas():

    return render_template(
        "ventas.html",
        ventas=VentaService.listar(),
        productos=ProductoService.listar()
    )


@venta_bp.route(
    "/ventas/registrar",
    methods=["POST"]
)
def registrar():

    producto_id = request.form["producto_id"]

    cantidad = int(request.form["cantidad"])

    fecha = request.form["fecha"]

    VentaService.registrar(
        producto_id,
        cantidad,
        fecha
    )

    return redirect(
        url_for("ventas.ventas")
    )