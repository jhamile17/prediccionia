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

    if isinstance(modelo_cargado, dict):

        modelo = modelo_cargado["modelo"]

    else:

        modelo = modelo_cargado

except Exception as e:

    print(
        json.dumps({
            "success": False,
            "error": f"No se pudo cargar el modelo: {str(e)}"
        }, ensure_ascii=False)
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
    Convierte 'anio' recibido desde Laravel a 'año',
    que es el nombre utilizado por el modelo.
    """

    if not isinstance(datos, dict):
        return datos

    datos_normalizados = datos.copy()

    if "anio" in datos_normalizados:
        datos_normalizados["año"] = datos_normalizados["anio"]
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

        dato = normalizar_datos(dato)

        fila = {}

        for variable in VARIABLES_MODELO:

            if variable not in dato:

                raise ValueError(
                    f"Falta la variable requerida: {variable}"
                )

            fila[variable] = dato[variable]

        filas.append(fila)
        datos_normalizados.append(dato)

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
            "producto_id": dato.get("producto_id"),
            "fecha": dato.get("fecha"),
            "prediccion": prediccion
        })

    return {
        "success": True,
        "predicciones": resultado
    }


# ============================================================
# PREDICCIÓN MENSUAL RECURSIVA
# ============================================================

def predecir_mensual(datos):

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
            int(round(float(cantidad)))
        )

    # --------------------------------------------------------
    # ORDENAR FECHAS
    # --------------------------------------------------------

    fechas_ordenadas = sorted(
        fechas,
        key=lambda x: x.get("fecha", "")
    )

    resultados = []

    # --------------------------------------------------------
    # PROCESAR CADA DÍA
    # --------------------------------------------------------

    for dia in fechas_ordenadas:

        fecha = dia.get("fecha")

        if not fecha:

            raise ValueError(
                "Una fecha de predicción está vacía."
            )

        fecha = str(fecha)

        fecha_dt = pd.Timestamp(fecha)

        # ====================================================
        # DEMANDA ANTERIOR
        # ====================================================

        fecha_anterior = (
            fecha_dt - pd.Timedelta(days=1)
        ).strftime("%Y-%m-%d")

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
            fecha_dt - pd.Timedelta(days=7)
        ).strftime("%Y-%m-%d")

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
            fecha_dt - pd.Timedelta(days=14)
        ).strftime("%Y-%m-%d")

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
                fecha_dt - pd.Timedelta(days=i)
            ).strftime("%Y-%m-%d")

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
                fecha_dt - pd.Timedelta(days=i)
            ).strftime("%Y-%m-%d")

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

        # Preferimos el valor enviado por Laravel.
        # Como respaldo utilizamos pandas:
        # lunes=1 ... domingo=7
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
                "anio",
                dia.get(
                    "año",
                    fecha_dt.year
                )
            )
        )

        es_fin_de_semana = int(
            dia.get(
                "es_fin_de_semana",
                1 if fecha_dt.dayofweek >= 5 else 0
            )
        )

        es_dia_especial = int(
            dia.get(
                "es_dia_especial",
                0
            )
        )

        # ====================================================
        # CONSTRUIR LAS VARIABLES
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
            int(round(prediccion))
        )

        # ====================================================
        # GUARDAR PREDICCIÓN EN HISTORIAL
        # ====================================================

        historial[fecha] = prediccion

        # ====================================================
        # RESPUESTA
        # ====================================================

        variables_respuesta = fila.copy()

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
    Predicción mensual múltiple optimizada.

    Procesa todos los productos por día en lote.

    Antes:
        producto -> días -> predict() individual

    Ahora:
        día -> todos los productos -> un solo predict()

    Se mantiene la naturaleza recursiva porque las predicciones
    de un día se agregan al historial antes de procesar el siguiente.
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

    # ========================================================
    # PREPARAR ESTADO DE CADA PRODUCTO
    # ========================================================

    estados = []

    for solicitud in solicitudes:

        if not isinstance(
            solicitud,
            dict
        ):
            raise ValueError(
                "Cada solicitud mensual debe ser un objeto."
            )

        producto = solicitud.get(
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

        historial_original = solicitud.get(
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

        fechas = solicitud.get(
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

        # ----------------------------------------------------
        # HISTORIAL
        # ----------------------------------------------------

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

        # ----------------------------------------------------
        # FECHAS
        # ----------------------------------------------------

        fechas_ordenadas = sorted(
            fechas,
            key=lambda x: x.get(
                "fecha",
                ""
            )
        )

        estados.append({

            "producto_id":
                int(producto_id),

            "categoria_id":
                int(categoria_id),

            "historial":
                historial,

            "fechas":
                fechas_ordenadas,

            "resultados":
                []
        })


    # ========================================================
    # OBTENER TODAS LAS FECHAS
    # ========================================================

    fechas_globales = sorted({
        str(fecha.get("fecha"))
        for estado in estados
        for fecha in estado["fechas"]
        if fecha.get("fecha")
    })


    # ========================================================
    # PROCESAMIENTO RECURSIVO POR DÍA
    # ========================================================

    for fecha in fechas_globales:

        filas = []
        referencias = []

        fecha_dt = pd.Timestamp(
            fecha
        )

        # ----------------------------------------------------
        # CONSTRUIR UNA FILA POR PRODUCTO
        # ----------------------------------------------------

        for estado in estados:

            # Buscar si este producto tiene predicción
            # para la fecha actual.
            dia_actual = None

            for dia in estado["fechas"]:

                if str(
                    dia.get("fecha")
                ) == fecha:

                    dia_actual = dia
                    break

            if dia_actual is None:
                continue

            historial = estado[
                "historial"
            ]

            # ================================================
            # DEMANDA ANTERIOR
            # ================================================

            fecha_anterior = (
                fecha_dt -
                pd.Timedelta(days=1)
            ).strftime("%Y-%m-%d")

            demanda_anterior = int(
                historial.get(
                    fecha_anterior,
                    0
                )
            )

            # ================================================
            # DEMANDA 7 DÍAS
            # ================================================

            fecha_7 = (
                fecha_dt -
                pd.Timedelta(days=7)
            ).strftime("%Y-%m-%d")

            demanda_7_dias = int(
                historial.get(
                    fecha_7,
                    0
                )
            )

            # ================================================
            # DEMANDA 14 DÍAS
            # ================================================

            fecha_14 = (
                fecha_dt -
                pd.Timedelta(days=14)
            ).strftime("%Y-%m-%d")

            demanda_14_dias = int(
                historial.get(
                    fecha_14,
                    0
                )
            )

            # ================================================
            # PROMEDIO 7 DÍAS
            # ================================================

            valores_7 = []

            for i in range(1, 8):

                fecha_hist = (
                    fecha_dt -
                    pd.Timedelta(days=i)
                ).strftime("%Y-%m-%d")

                valores_7.append(
                    historial.get(
                        fecha_hist,
                        0
                    )
                )

            promedio_7_dias = (
                sum(valores_7) / 7
            )

            # ================================================
            # PROMEDIO 30 DÍAS
            # ================================================

            valores_30 = []

            for i in range(1, 31):

                fecha_hist = (
                    fecha_dt -
                    pd.Timedelta(days=i)
                ).strftime("%Y-%m-%d")

                valores_30.append(
                    historial.get(
                        fecha_hist,
                        0
                    )
                )

            promedio_30_dias = (
                sum(valores_30) / 30
            )

            # ================================================
            # VARIABLES TEMPORALES
            # ================================================

            dia_semana = int(
                dia_actual.get(
                    "dia_semana",
                    fecha_dt.isoweekday()
                )
            )

            mes = int(
                dia_actual.get(
                    "mes",
                    fecha_dt.month
                )
            )

            anio = int(
                dia_actual.get(
                    "anio",
                    fecha_dt.year
                )
            )

            es_fin_de_semana = int(
                dia_actual.get(
                    "es_fin_de_semana",
                    1
                    if fecha_dt.dayofweek >= 5
                    else 0
                )
            )

            es_dia_especial = int(
                dia_actual.get(
                    "es_dia_especial",
                    0
                )
            )

            # ================================================
            # FILA DEL MODELO
            # ================================================

            fila = {

                "producto_id":
                    int(
                        estado["producto_id"]
                    ),

                "categoria_id":
                    int(
                        estado["categoria_id"]
                    ),

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

            filas.append(
                fila
            )

            referencias.append(
                {
                    "estado": estado,
                    "dia": dia_actual,
                    "fecha": fecha,
                    "fila": fila
                }
            )


        # ====================================================
        # PREDICCIÓN EN LOTE
        # ====================================================

        if not filas:
            continue

        entrada = pd.DataFrame(
            filas,
            columns=VARIABLES_MODELO
        )

        predicciones = modelo.predict(
            entrada
        )

        # ====================================================
        # ACTUALIZAR HISTORIALES
        # ====================================================

        for referencia, prediccion in zip(
            referencias,
            predicciones
        ):

            prediccion = max(
                0,
                int(
                    round(
                        prediccion
                    )
                )
            )

            estado = referencia[
                "estado"
            ]

            fecha_actual = referencia[
                "fecha"
            ]

            # -----------------------------------------------
            # GUARDAR PREDICCIÓN PARA EL SIGUIENTE DÍA
            # -----------------------------------------------

            estado["historial"][
                fecha_actual
            ] = prediccion

            # -----------------------------------------------
            # PREPARAR VARIABLES DE RESPUESTA
            # -----------------------------------------------

            variables_respuesta = (
                referencia["fila"].copy()
            )

            variables_respuesta["anio"] = (
                variables_respuesta["año"]
            )

            del variables_respuesta[
                "año"
            ]

            estado["resultados"].append({

                "producto_id":
                    estado["producto_id"],

                "fecha":
                    fecha_actual,

                "prediccion":
                    prediccion,

                "variables":
                    variables_respuesta
            })


    # ========================================================
    # CONSTRUIR RESPUESTA FINAL
    # ========================================================

    resultados = []

    for estado in estados:

        resultados.append({

            "producto_id":
                estado["producto_id"],

            "predicciones":
                estado["resultados"]
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
            and datos.get("modo") == "mensual_multiple"
        ):

            resultado = predecir_mensual_multiple(
                datos
            )

        # ====================================================
        # MODO MENSUAL
        # ====================================================

        elif (
            isinstance(datos, dict)
            and datos.get("modo") == "mensual"
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

        else:

            raise ValueError(
                "Formato JSON no válido."
            )

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