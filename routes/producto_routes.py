from flask import Blueprint, render_template
from services.producto_service import ProductoService

producto_bp = Blueprint(
    "productos",
    __name__
)


@producto_bp.route("/productos")
def productos():

    lista = ProductoService.listar()

    return render_template(
        "productos.html",
        productos=lista
    )