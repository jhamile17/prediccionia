import sys
import json
import os
import joblib
import pandas as pd


# ============================================================
# CONFIGURACIÓN
# ============================================================

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

MODELO_PATH = os.path.join(
    BASE_DIR,
    "modelos",
    "modelo_demanda.pkl"
)


# ============================================================
# CARGAR MODELO
# ============================================================

try:

    modelo_cargado = joblib.load(
        MODELO_PATH
    )

    # El .pkl contiene un diccionario
    # y el modelo está dentro de la clave "modelo".
    if isinstance(
        modelo_cargado,
        dict
    ):

        modelo = modelo_cargado["modelo"]

    else:

        modelo = modelo_cargado

except Exception as e:

    print(
        json.dumps({
            "success": False,
            "error":
                f"No se pudo cargar el modelo: {str(e)}"
        })
    )

    sys.exit(1)


# ============================================================
# VARIABLES DEL MODELO
# ============================================================

VARIABLES_MODELO = [
    "producto_id",
    "categoria_id",
    "demanda_anterior",
    "demanda_7_dias",
    "demanda_14_dias",
    "promedio_7_dias",
    "promedio_30_dias",
    "dia_semana",
    "mes",
    "año",
    "es_fin_de_semana",
    "es_dia_especial"
]


# ============================================================
# NORMALIZAR DATOS DE ENTRADA
# ============================================================

def normalizar_datos(datos):
    """
    Normaliza nombres de variables recibidas.

    El modelo utiliza internamente "año".
    Para evitar problemas de codificación desde
    PowerShell/Laravel también aceptamos "anio".
    """

    if not isinstance(datos, dict):
        return datos

    datos_normalizados = datos.copy()

    # Compatibilidad con "anio"
    if "anio" in datos_normalizados:

        datos_normalizados["año"] = (
            datos_normalizados["anio"]
        )

        del datos_normalizados["anio"]

    return datos_normalizados


# ============================================================
# PREDICCIÓN INDIVIDUAL
# ============================================================

def predecir_individual(datos):

    datos = normalizar_datos(datos)

    fila = {}

    for variable in VARIABLES_MODELO:

        if variable not in datos:

            raise ValueError(
                f"Falta la variable requerida: {variable}"
            )

        fila[variable] = datos[variable]

    entrada = pd.DataFrame(
        [fila],
        columns=VARIABLES_MODELO
    )

    prediccion = modelo.predict(
        entrada
    )[0]

    prediccion = max(
        0,
        int(round(prediccion))
    )

    return {
        "success": True,
        "prediccion": prediccion
    }


# ============================================================
# PREDICCIÓN MÚLTIPLE
# ============================================================

def predecir_multiple(datos):

    if not isinstance(datos, list):

        raise ValueError(
            "La entrada múltiple debe ser una lista."
        )

    if len(datos) == 0:

        return {
            "success": True,
            "predicciones": []
        }

    filas = []

    datos_normalizados = []

    for dato in datos:

        dato = normalizar_datos(
            dato
        )

        fila = {}

        for variable in VARIABLES_MODELO:

            if variable not in dato:

                raise ValueError(
                    f"Falta la variable requerida: {variable}"
                )

            fila[variable] = dato[variable]

        filas.append(
            fila
        )

        datos_normalizados.append(
            dato
        )

    entrada = pd.DataFrame(
        filas,
        columns=VARIABLES_MODELO
    )

    predicciones = modelo.predict(
        entrada
    )

    resultado = []

    for dato, prediccion in zip(
        datos_normalizados,
        predicciones
    ):

        prediccion = max(
            0,
            int(round(prediccion))
        )

        resultado.append({

            "producto_id":
                dato.get("producto_id"),

            "fecha":
                dato.get("fecha"),

            "prediccion":
                prediccion
        })

    return {
        "success": True,
        "predicciones": resultado
    }


# ============================================================
# PREDICCIÓN MENSUAL RECURSIVA
# ============================================================

def predecir_mensual(datos):

    """
    Realiza predicción recursiva.

    Recibe:

    {
        "modo": "mensual",

        "producto": {
            "producto_id": 1,
            "categoria_id": 1
        },

        "historial": {
            "2026-08-01": 5,
            "2026-08-02": 7
        },

        "fechas": [
            {
                "fecha": "2026-09-02",
                "dia_semana": 3,
                "mes": 9,
                "año": 2026,
                "es_fin_de_semana": 0,
                "es_dia_especial": 0
            }
        ]
    }

    Las predicciones generadas se agregan al historial
    para utilizarse en los días siguientes.
    """

    producto = datos.get(
        "producto"
    )

    if not isinstance(
        producto,
        dict
    ):

        raise ValueError(
            "Falta la información del producto."
        )

    producto_id = producto.get(
        "producto_id"
    )

    categoria_id = producto.get(
        "categoria_id"
    )

    if producto_id is None:

        raise ValueError(
            "Falta producto_id."
        )

    if categoria_id is None:

        raise ValueError(
            "Falta categoria_id."
        )

    historial_original = datos.get(
        "historial",
        {}
    )

    if not isinstance(
        historial_original,
        dict
    ):

        raise ValueError(
            "El historial debe ser un objeto."
        )

    fechas = datos.get(
        "fechas",
        []
    )

    if not isinstance(
        fechas,
        list
    ):

        raise ValueError(
            "Las fechas deben ser una lista."
        )

    # --------------------------------------------------------
    # COPIAR HISTORIAL
    # --------------------------------------------------------

    historial = {}

    for fecha, cantidad in historial_original.items():

        historial[str(fecha)] = max(
            0,
            int(
                round(
                    float(cantidad)
                )
            )
        )

    # --------------------------------------------------------
    # ORDENAR FECHAS
    # --------------------------------------------------------

    fechas_ordenadas = sorted(
        fechas,
        key=lambda x: x.get(
            "fecha",
            ""
        )
    )

    resultados = []

    # --------------------------------------------------------
    # PROCESAR CADA DÍA
    # --------------------------------------------------------

    for dia in fechas_ordenadas:

        fecha = dia.get(
            "fecha"
        )

        if not fecha:

            raise ValueError(
                "Una fecha de predicción está vacía."
            )

        fecha = str(
            fecha
        )

        fecha_dt = pd.Timestamp(
            fecha
        )

        # ====================================================
        # DEMANDA ANTERIOR
        # ====================================================

        fecha_anterior = (
            fecha_dt -
            pd.Timedelta(days=1)
        ).strftime(
            "%Y-%m-%d"
        )

        demanda_anterior = int(
            historial.get(
                fecha_anterior,
                0
            )
        )

        # ====================================================
        # DEMANDA 7 DÍAS
        # ====================================================

        fecha_7 = (
            fecha_dt -
            pd.Timedelta(days=7)
        ).strftime(
            "%Y-%m-%d"
        )

        demanda_7_dias = int(
            historial.get(
                fecha_7,
                0
            )
        )

        # ====================================================
        # DEMANDA 14 DÍAS
        # ====================================================

        fecha_14 = (
            fecha_dt -
            pd.Timedelta(days=14)
        ).strftime(
            "%Y-%m-%d"
        )

        demanda_14_dias = int(
            historial.get(
                fecha_14,
                0
            )
        )

        # ====================================================
        # PROMEDIO 7 DÍAS
        # ====================================================

        valores_7 = []

        for i in range(1, 8):

            fecha_hist = (
                fecha_dt -
                pd.Timedelta(days=i)
            ).strftime(
                "%Y-%m-%d"
            )

            valores_7.append(
                historial.get(
                    fecha_hist,
                    0
                )
            )

        promedio_7_dias = (
            sum(valores_7) / 7
        )

        # ====================================================
        # PROMEDIO 30 DÍAS
        # ====================================================

        valores_30 = []

        for i in range(1, 31):

            fecha_hist = (
                fecha_dt -
                pd.Timedelta(days=i)
            ).strftime(
                "%Y-%m-%d"
            )

            valores_30.append(
                historial.get(
                    fecha_hist,
                    0
                )
            )

        promedio_30_dias = (
            sum(valores_30) / 30
        )

        # ====================================================
        # VARIABLES TEMPORALES
        # ====================================================

        dia_semana = int(
            dia.get(
                "dia_semana",
                fecha_dt.isoweekday()
            )
        )

        mes = int(
            dia.get(
                "mes",
                fecha_dt.month
            )
        )

        anio = int(
            dia.get(
                "año",
                dia.get(
                    "anio",
                    fecha_dt.year
                )
            )
        )

        es_fin_de_semana = int(
            dia.get(
                "es_fin_de_semana",
                1 if fecha_dt.dayofweek >= 5
                else 0
            )
        )

        es_dia_especial = int(
            dia.get(
                "es_dia_especial",
                0
            )
        )

        # ====================================================
        # CONSTRUIR LAS 12 VARIABLES
        # ====================================================

        fila = {

            "producto_id":
                int(producto_id),

            "categoria_id":
                int(categoria_id),

            "demanda_anterior":
                demanda_anterior,

            "demanda_7_dias":
                demanda_7_dias,

            "demanda_14_dias":
                demanda_14_dias,

            "promedio_7_dias":
                round(
                    promedio_7_dias,
                    2
                ),

            "promedio_30_dias":
                round(
                    promedio_30_dias,
                    2
                ),

            "dia_semana":
                dia_semana,

            "mes":
                mes,

            "año":
                anio,

            "es_fin_de_semana":
                es_fin_de_semana,

            "es_dia_especial":
                es_dia_especial
        }

        # ====================================================
        # PREDICCIÓN
        # ====================================================

        entrada = pd.DataFrame(
            [fila],
            columns=VARIABLES_MODELO
        )

        prediccion = modelo.predict(
            entrada
        )[0]

        prediccion = max(
            0,
            int(
                round(
                    prediccion
                )
            )
        )

        # ====================================================
        # GUARDAR PREDICCIÓN EN HISTORIAL
        # ====================================================

        historial[fecha] = prediccion

        # ====================================================
        # RESULTADO
        # ====================================================

        variables_respuesta = fila.copy()

        # Para Laravel usamos "anio".
        if "año" in variables_respuesta:

            variables_respuesta["anio"] = (
                variables_respuesta["año"]
            )

            del variables_respuesta["año"]

        resultados.append({

            "producto_id":
                int(producto_id),

            "fecha":
                fecha,

            "prediccion":
                prediccion,

            "variables":
                variables_respuesta
        })

    return {

        "success": True,

        "producto_id":
            int(producto_id),

        "predicciones":
            resultados
    }


# ============================================================
# PREDICCIÓN MENSUAL MÚLTIPLE
# ============================================================

def predecir_mensual_multiple(datos):

    """
    Realiza predicciones mensuales para varios productos
    en una sola ejecución de Python.

    Recibe:

    {
        "modo": "mensual_multiple",

        "solicitudes": [
            {
                "producto": {
                    "producto_id": 1,
                    "categoria_id": 1
                },

                "historial": {
                    "2026-08-01": 5,
                    "2026-08-02": 7
                },

                "fechas": [
                    {
                        "fecha": "2026-09-02",
                        "dia_semana": 3,
                        "mes": 9,
                        "anio": 2026,
                        "es_fin_de_semana": 0,
                        "es_dia_especial": 0
                    }
                ]
            }
        ]
    }

    El modelo se carga una sola vez y se reutiliza
    para todos los productos.
    """

    solicitudes = datos.get(
        "solicitudes",
        []
    )

    if not isinstance(
        solicitudes,
        list
    ):

        raise ValueError(
            "Las solicitudes deben ser una lista."
        )

    if len(solicitudes) == 0:

        return {
            "success": True,
            "predicciones": []
        }

    resultados = []

    for solicitud in solicitudes:

        if not isinstance(
            solicitud,
            dict
        ):

            raise ValueError(
                "Cada solicitud mensual debe ser un objeto."
            )

        resultado = predecir_mensual(
            solicitud
        )

        resultados.append({

            "producto_id":
                resultado.get(
                    "producto_id"
                ),

            "predicciones":
                resultado.get(
                    "predicciones",
                    []
                )
        })

    return {

        "success": True,

        "predicciones":
            resultados
    }


# ============================================================
# LEER JSON
# ============================================================

def main():

    try:

        entrada = sys.stdin.read()

        if not entrada.strip():

            raise ValueError(
                "No se recibió información."
            )

        datos = json.loads(
            entrada
        )

        # ====================================================
        # MODO MENSUAL MÚLTIPLE
        # ====================================================

        if (
            isinstance(datos, dict)
            and
            datos.get("modo") == "mensual_multiple"
        ):

            resultado = predecir_mensual_multiple(
                datos
            )

        # ====================================================
        # MODO MENSUAL
        # ====================================================

        elif (
            isinstance(datos, dict)
            and
            datos.get("modo") == "mensual"
        ):

            resultado = predecir_mensual(
                datos
            )

        # ====================================================
        # LISTA = MÚLTIPLE
        # ====================================================

        elif isinstance(
            datos,
            list
        ):

            resultado = predecir_multiple(
                datos
            )

        # ====================================================
        # DICCIONARIO = INDIVIDUAL
        # ====================================================

        elif isinstance(
            datos,
            dict
        ):

            resultado = predecir_individual(
                datos
            )

        # ====================================================
        # FORMATO NO VÁLIDO
        # ====================================================

        else:

            raise ValueError(
                "Formato JSON no válido."
            )

        # ====================================================
        # RESPUESTA JSON
        # ====================================================

        print(
            json.dumps(
                resultado,
                ensure_ascii=False
            )
        )

    except Exception as e:

        print(
            json.dumps({
                "success": False,
                "error": str(e)
            }, ensure_ascii=False)
        )

        sys.exit(1)


# ============================================================
# EJECUTAR
# ============================================================

if __name__ == "__main__":

    main()