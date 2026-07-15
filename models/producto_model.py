from database.db import get_connection


class ProductoModel:

    @staticmethod
    def obtener_todos():

        conexion = get_connection()

        try:
            with conexion.cursor() as cursor:

                sql = """
                SELECT
                    p.id,
                    c.nombre AS categoria,
                    p.nombre,
                    p.precio,
                    p.stock,
                    p.stock_minimo
                FROM productos p
                INNER JOIN categorias c
                    ON p.categoria_id = c.id
                ORDER BY p.id
                """

                cursor.execute(sql)

                productos = cursor.fetchall()

                return productos

        finally:
            conexion.close()