from database.db import get_connection


class ProductoModel:

    @staticmethod
    def obtener_todos():

        conexion = get_connection()

        try:
            with conexion.cursor() as cursor:

                sql = """
                SELECT
                    id,
                    nombre,
                    precio,
                    stock
                FROM productos
                ORDER BY id
                """

                cursor.execute(sql)

                productos = cursor.fetchall()

                return productos

        finally:
            conexion.close()