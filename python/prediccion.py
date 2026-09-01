import sys
import json
import joblib
import pandas as pd
import os

# ============================================================
# CONFIGURACIÓN
# ============================================================
RUTA_MODELO = os.path.join(
    os.path.dirname(os.path.abspath(__file__)),
    "modelos",
    "modelo_demanda.pkl"
)

# ============================================================
# CARGAR MODELO
# ============================================================

try:
    paquete = joblib.load(RUTA_MODELO)

    # El .pkl contiene un diccionario
    modelo = paquete["modelo"]
    features = paquete["features"]

    mae = paquete.get("mae")
    rmse = paquete.get("rmse")
    r2 = paquete.get("r2")

except Exception as e:
    print(json.dumps({
        "success": False,
        "error": f"No se pudo cargar el modelo: {str(e)}"
    }, ensure_ascii=False))

    sys.exit(1)


# ============================================================
# FUNCIÓN DE PREDICCIÓN
# ============================================================

def predecir(datos):

    # --------------------------------------------------------
    # Compatibilidad: aceptar "anio" y convertirlo a "año"
    # --------------------------------------------------------
    if "anio" in datos and "año" not in datos:
        datos["año"] = datos["anio"]

    # --------------------------------------------------------
    # Verificar variables requeridas
    # --------------------------------------------------------
    faltantes = [
        variable
        for variable in features
        if variable not in datos
    ]

    if faltantes:
        raise ValueError(
            f"Faltan variables requeridas: {faltantes}"
        )

    # --------------------------------------------------------
    # Crear DataFrame respetando EXACTAMENTE el orden
    # del entrenamiento
    # --------------------------------------------------------
    entrada = pd.DataFrame(
        [[datos[variable] for variable in features]],
        columns=features
    )

    # --------------------------------------------------------
    # Predicción
    # --------------------------------------------------------
    prediccion = modelo.predict(entrada)[0]

    # La demanda no puede ser negativa
    prediccion = max(0, round(float(prediccion)))

    return prediccion


# ============================================================
# DATOS DE PRUEBA
# ============================================================

datos_prueba = {
    "producto_id": 1,
    "categoria_id": 1,
    "demanda_anterior": 6,
    "demanda_7_dias": 9,
    "demanda_14_dias": 6,
    "promedio_7_dias": 7.43,
    "promedio_30_dias": 6.90,
    "dia_semana": 3,
    "mes": 9,
    "año": 2026,
    "es_fin_de_semana": 0,
    "es_dia_especial": 0
}


# ============================================================
# MODO CONSOLA / JSON
# ============================================================

try:

    # Si llega información por STDIN, usamos esa información
    if not sys.stdin.isatty():

        entrada_json = sys.stdin.read().strip()

        if entrada_json:

            datos = json.loads(entrada_json)

            resultado = predecir(datos)

            print(json.dumps({
                "success": True,
                "producto_id": datos["producto_id"],
                "prediccion": resultado
            }, ensure_ascii=False))

            sys.exit(0)

    # --------------------------------------------------------
    # Si no llega JSON, ejecutamos prueba local
    # --------------------------------------------------------

    resultado = predecir(datos_prueba)

    print("Modelo cargado correctamente.")
    print(f"Variables utilizadas: {len(features)}")

    if mae is not None:
        print(f"MAE: {mae}")

    if rmse is not None:
        print(f"RMSE: {rmse}")

    if r2 is not None:
        print(f"R²: {r2}")

    print()
    print("=" * 50)
    print("PREDICCIÓN DE DEMANDA")
    print("=" * 50)
    print(f"Producto ID : {datos_prueba['producto_id']}")
    print("Fecha       : 2026-09-02")
    print(f"Demanda     : {resultado} unidades")
    print("=" * 50)


except Exception as e:

    print(json.dumps({
        "success": False,
        "error": str(e)
    }, ensure_ascii=False))
