from flask import Flask
from database.db import get_connection

app = Flask(__name__)


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
        """

    except Exception as e:

        return f"<h3>Error:</h3><p>{e}</p>"


if __name__ == "__main__":
    app.run(debug=True)