<?php

declare(strict_types=1);

/**
 * Interpreta el contenido programático escrito como módulos.
 * Misma regla del sistema anterior: "Módulo 1: Título" y viñetas con guion.
 */
class MallaParser
{
    public static function parse(string $malla): array
    {
        $modulos = [];
        $actual = null;

        foreach (preg_split('/\r\n|\r|\n/', trim($malla)) ?: [] as $linea) {
            $linea = trim($linea);
            if ($linea === '') {
                continue;
            }

            if (preg_match('/^M[oó]dulo\s*\d+\s*:?\s*(.+)$/iu', $linea, $coincidencia)) {
                if ($actual) {
                    $modulos[] = $actual;
                }
                $actual = ['title' => trim($coincidencia[1]), 'items' => []];
                continue;
            }

            if (preg_match('/^[-•*]\s*(.+)$/u', $linea, $coincidencia)) {
                if (!$actual) {
                    $actual = ['title' => '', 'items' => []];
                }
                $actual['items'][] = trim($coincidencia[1]);
                continue;
            }

            if (!$actual) {
                $actual = ['title' => $linea, 'items' => []];
            } else {
                $actual['items'][] = $linea;
            }
        }

        if ($actual) {
            $modulos[] = $actual;
        }

        if ($modulos === [] && trim($malla) !== '') {
            $modulos[] = ['title' => 'Contenido del curso', 'items' => [trim($malla)]];
        }

        return $modulos;
    }

    public static function paraFormulario(?string $malla): array
    {
        $lista = [];
        foreach (self::parse((string) $malla) as $modulo) {
            $lista[] = [
                'titulo' => (string) ($modulo['title'] ?? ''),
                'contenido' => implode("\n", $modulo['items'] ?? []),
            ];
        }

        return $lista;
    }

    public static function componer(array $modulos): string
    {
        $lineas = [];
        $numero = 1;
        foreach ($modulos as $modulo) {
            $titulo = trim((string) ($modulo['titulo'] ?? ''));
            if ($titulo === '') {
                continue;
            }
            $lineas[] = 'Módulo ' . $numero . ': ' . $titulo;
            foreach (preg_split('/\r\n|\r|\n/', (string) ($modulo['contenido'] ?? '')) ?: [] as $linea) {
                $linea = trim($linea);
                if ($linea === '') {
                    continue;
                }
                $linea = preg_replace('/^[-•*]\s*/u', '', $linea) ?? $linea;
                $lineas[] = '- ' . trim($linea);
            }
            $numero++;
        }

        return implode("\n", $lineas);
    }

    public static function splitColumns(array $modulos): array
    {
        if (count($modulos) <= 1) {
            return [$modulos, []];
        }
        $mitad = (int) ceil(count($modulos) / 2);

        return [
            array_slice($modulos, 0, $mitad),
            array_slice($modulos, $mitad),
        ];
    }
}
