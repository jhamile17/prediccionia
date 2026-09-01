<?php

namespace App\Services;

use RuntimeException;

class PredictionService
{
    /**
     * Ejecuta el modelo Python y devuelve la predicción.
     */
    public function predecir(array $datos): array
    {
        $python = 'python';
        $script = base_path('python/prediccion.py');

        if (!file_exists($script)) {
            throw new RuntimeException(
                "No se encontró el script Python: {$script}"
            );
        }

        // Evitamos problemas de codificación con la ñ.
        // Python recibirá "anio" y lo convertirá a "año".
        if (isset($datos['año'])) {
            $datos['anio'] = $datos['año'];
            unset($datos['año']);
        }

        $json = json_encode(
            $datos,
            JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            throw new RuntimeException(
                'No se pudieron convertir los datos a JSON.'
            );
        }

        /*
         * En Windows enviamos el JSON mediante un archivo temporal.
         * Esto evita problemas con caracteres especiales y comillas
         * al utilizar PowerShell/cmd.
         */
        $archivoTemporal = tempnam(
            sys_get_temp_dir(),
            'prediccion_'
        );

        if ($archivoTemporal === false) {
            throw new RuntimeException(
                'No se pudo crear el archivo temporal.'
            );
        }

        file_put_contents($archivoTemporal, $json);

        try {

            $comando = sprintf(
                '%s %s < %s 2>&1',
                escapeshellcmd($python),
                escapeshellarg($script),
                escapeshellarg($archivoTemporal)
            );

            $salida = shell_exec($comando);

            if ($salida === null || trim($salida) === '') {
                throw new RuntimeException(
                    'Python no devolvió ninguna respuesta.'
                );
            }

            /*
             * Python devuelve JSON.
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

            if (($resultado['success'] ?? false) !== true) {
                throw new RuntimeException(
                    $resultado['error']
                    ?? 'Python devolvió un error desconocido.'
                );
            }

            return $resultado;

        } finally {

            if (file_exists($archivoTemporal)) {
                unlink($archivoTemporal);
            }
        }
    }
}
