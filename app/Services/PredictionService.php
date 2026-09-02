<?php

namespace App\Services;

use RuntimeException;

class PredictionService
{
    /**
     * ============================================================
     * PREDICCIÓN INDIVIDUAL
     * ============================================================
     *
     * Envía una fila de datos a Python y devuelve una predicción.
     */
    public function predecir(array $datos): array
    {
        return $this->ejecutarPython($datos, 'prediccion_');
    }


    /**
     * ============================================================
     * PREDICCIÓN MÚLTIPLE
     * ============================================================
     *
     * Envía varias filas a Python en una sola ejecución.
     */
    public function predecirMultiple(array $datos): array
    {
        return $this->ejecutarPython($datos, 'predicciones_');
    }


    /**
     * ============================================================
     * EJECUTAR PYTHON
     * ============================================================
     *
     * Centraliza la comunicación Laravel → Python.
     */
    private function ejecutarPython(
        array $datos,
        string $prefijoTemporal
    ): array {

        $python = 'python';

        $script = base_path(
            'python/prediccion.py'
        );

        /*
         * Verificar que exista el script.
         */
        if (!file_exists($script)) {

            throw new RuntimeException(
                "No se encontró el script Python: {$script}"
            );
        }

        /*
         * ========================================================
         * NORMALIZAR "año" → "anio"
         * ========================================================
         *
         * Python acepta ambos nombres.
         *
         * Usamos "anio" en la comunicación externa para
         * evitar problemas de codificación.
         */
        if ($this->esFilaIndividual($datos)) {

            if (isset($datos['año'])) {

                $datos['anio'] = $datos['año'];

                unset($datos['año']);
            }

        } else {

            foreach ($datos as $indice => $fila) {

                if (
                    is_array($fila) &&
                    isset($fila['año'])
                ) {

                    $datos[$indice]['anio'] =
                        $fila['año'];

                    unset(
                        $datos[$indice]['año']
                    );
                }
            }
        }

        /*
         * ========================================================
         * CONVERTIR A JSON
         * ========================================================
         */
        $json = json_encode(
            $datos,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        );

        if ($json === false) {

            throw new RuntimeException(
                'No se pudieron convertir los datos a JSON.'
            );
        }

        /*
         * ========================================================
         * ARCHIVO TEMPORAL
         * ========================================================
         */
        $archivoTemporal = tempnam(
            sys_get_temp_dir(),
            $prefijoTemporal
        );

        if ($archivoTemporal === false) {

            throw new RuntimeException(
                'No se pudo crear el archivo temporal.'
            );
        }

        /*
         * Guardar JSON.
         */
        if (
            file_put_contents(
                $archivoTemporal,
                $json
            ) === false
        ) {

            unlink($archivoTemporal);

            throw new RuntimeException(
                'No se pudo escribir el archivo temporal.'
            );
        }

        try {

            /*
             * ====================================================
             * EJECUTAR PYTHON
             * ====================================================
             */
            $comando = sprintf(
                '%s %s < %s 2>&1',
                escapeshellcmd($python),
                escapeshellarg($script),
                escapeshellarg($archivoTemporal)
            );

            $salida = shell_exec($comando);

            /*
             * ====================================================
             * VALIDAR RESPUESTA
             * ====================================================
             */
            if (
                $salida === null ||
                trim($salida) === ''
            ) {

                throw new RuntimeException(
                    'Python no devolvió ninguna respuesta.'
                );
            }

            /*
             * ====================================================
             * DECODIFICAR JSON
             * ====================================================
             */
            $resultado = json_decode(
                trim($salida),
                true
            );

            if (!is_array($resultado)) {

                throw new RuntimeException(
                    'La respuesta de Python no es un JSON válido: '
                    . $salida
                );
            }

            /*
             * ====================================================
             * VERIFICAR ÉXITO
             * ====================================================
             */
            if (
                ($resultado['success'] ?? false)
                !== true
            ) {

                throw new RuntimeException(
                    $resultado['error']
                    ??
                    'Python devolvió un error desconocido.'
                );
            }

            return $resultado;

        } finally {

            /*
             * ====================================================
             * ELIMINAR ARCHIVO TEMPORAL
             * ====================================================
             */
            if (
                file_exists($archivoTemporal)
            ) {

                unlink($archivoTemporal);
            }
        }
    }


    /**
     * ============================================================
     * DETERMINAR SI ES UNA FILA INDIVIDUAL
     * ============================================================
     */
    private function esFilaIndividual(array $datos): bool
    {
        if ($datos === []) {
            return true;
        }

        foreach ($datos as $valor) {

            if (is_array($valor)) {
                return false;
            }
        }

        return true;
    }
}