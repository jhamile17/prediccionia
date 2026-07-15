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

                return cursor.fetchall()

        finally:
            conexion.close()