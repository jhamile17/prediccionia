from database.db import get_connection


class DashboardModel:

    @staticmethod
    def obtener_resumen():

        conexion = get_connection()

        try:

            with conexion.cursor() as cursor:

                cursor.execute("SELECT COUNT(*) total FROM productos")
                productos = cursor.fetchone()["total"]

                cursor.execute("SELECT COUNT(*) total FROM ventas")
                ventas = cursor.fetchone()["total"]

                cursor.execute("""
                    SELECT COUNT(*) total
                    FROM productos
                    WHERE stock <= stock_minimo
                """)

                riesgo = cursor.fetchone()["total"]

                return {
                    "productos": productos,
                    "ventas": ventas,
                    "riesgo": riesgo
                }

        finally:
            conexion.close()