from flask import Flask
from database.db import get_connection

from routes.producto_routes import producto_bp
from routes.venta_routes import venta_bp

app = Flask(__name__)

# Registrar Blueprints
app.register_blueprint(producto_bp)
app.register_blueprint(venta_bp)


@app.route("/")
def inicio():
    try:
        conexion = get_connection()

        with conexion.cursor() as cursor:
            cursor.execute("SELECT VERSION()")
            version = cursor.fetchone()

        conexion.close()

        return f"""
        <h2>✅ Conexión exitosa</h2>
        <p>Versión MySQL: {version['VERSION()']}</p>
        <hr>
        <a href="/productos">Ir al módulo de productos</a><br>
        <a href="/ventas">Ir al módulo de ventas</a>
        """

    except Exception as e:
        return f"<h3>❌ Error de conexión</h3><p>{e}</p>"


if __name__ == "__main__":
    app.run(debug=True)