from database.db import get_connection


class VentaModel:

    @staticmethod
    def obtener_todas():

        conexion = get_connection()

        try:
            with conexion.cursor() as cursor:

                sql = """
                SELECT
                    v.id,
                    p.nombre AS producto,
                    v.cantidad,
                    v.fecha
                FROM ventas v
                INNER JOIN productos p
                    ON v.producto_id = p.id
                ORDER BY v.id DESC
                """

                cursor.execute(sql)

                return cursor.fetchall()

        finally:
            conexion.close()


    @staticmethod
    def registrar(producto_id, cantidad, fecha):

        conexion = get_connection()

        try:
            with conexion.cursor() as cursor:

                sql = """
                INSERT INTO ventas
                (
                    producto_id,
                    cantidad,
                    fecha
                )
                VALUES
                (%s,%s,%s)
                """

                cursor.execute(sql, (
                    producto_id,
                    cantidad,
                    fecha
                ))

            conexion.commit()

            return True

        finally:

            conexion.close()