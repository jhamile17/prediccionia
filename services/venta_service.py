from database.db import get_connection
from models.venta_model import VentaModel


class VentaService:

    @staticmethod
    def listar():

        return VentaModel.obtener_todas()


    @staticmethod
    def registrar(producto_id, cantidad, fecha):

        conexion = get_connection()

        try:

            with conexion.cursor() as cursor:

                cursor.execute("""
                    SELECT stock
                    FROM productos
                    WHERE id=%s
                """, (producto_id,))

                producto = cursor.fetchone()

                if producto is None:

                    return False

                if producto["stock"] < cantidad:

                    return False

                nuevo_stock = producto["stock"] - cantidad

                cursor.execute("""
                    UPDATE productos
                    SET stock=%s
                    WHERE id=%s
                """, (
                    nuevo_stock,
                    producto_id
                ))

            conexion.commit()

        finally:

            conexion.close()

        return VentaModel.registrar(
            producto_id,
            cantidad,
            fecha
        )