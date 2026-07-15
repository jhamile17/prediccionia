import random
from datetime import datetime, timedelta

from faker import Faker

from database.db import get_connection

fake = Faker("es_ES")


def obtener_productos():

    conexion = get_connection()

    try:

        with conexion.cursor() as cursor:

            cursor.execute("""
                SELECT
                    id,
                    nombre
                FROM productos
                ORDER BY id
            """)

            return cursor.fetchall()

    finally:

        conexion.close()


def cantidad_producto(nombre, mes):

    nombre = nombre.lower()

    # Café Americano
    if "americano" in nombre:
        return random.randint(8, 20)

    # Capuccino
    if "capuccino" in nombre:
        return random.randint(6, 15)

    # Latte
    if "latte" in nombre:
        return random.randint(4, 10)

    # Frappé
    if "frapp" in nombre:

        if mes in [1, 2, 3, 11, 12]:

            return random.randint(10, 22)

        return random.randint(3, 8)

    # Cheesecake

    return random.randint(2, 7)


def generar():

    productos = obtener_productos()

    conexion = get_connection()

    try:

        with conexion.cursor() as cursor:

            fecha_inicio = datetime(2026, 1, 1)

            fecha_fin = datetime(2026, 12, 31)

            dias = (fecha_fin - fecha_inicio).days

            total = 1000

            for _ in range(total):

                producto = random.choice(productos)

                fecha = fecha_inicio + timedelta(
                    days=random.randint(0, dias)
                )

                cantidad = cantidad_producto(
                    producto["nombre"],
                    fecha.month
                )

                cursor.execute("""
                    INSERT INTO ventas
                    (
                        producto_id,
                        cantidad,
                        fecha
                    )
                    VALUES
                    (%s,%s,%s)
                """, (

                    producto["id"],
                    cantidad,
                    fecha.strftime("%Y-%m-%d")

                ))

        conexion.commit()

        print()
        print("============================")
        print("1000 ventas generadas")
        print("============================")

    finally:

        conexion.close()


if __name__ == "__main__":

    generar()