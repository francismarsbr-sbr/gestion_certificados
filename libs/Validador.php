<?php

declare(strict_types=1);

class Validador
{
    public static function texto(string $valor, string $etiqueta, int $minimo, int $maximo, bool $requerido = true): ?string
    {
        $valor = trim(preg_replace('/\s+/u', ' ', strip_tags($valor)) ?? '');
        if ($valor === '') {
            if ($requerido) {
                throw new AppException('Indique ' . $etiqueta . '.');
            }

            return null;
        }
        $largo = mb_strlen($valor);
        if ($largo < $minimo || $largo > $maximo) {
            throw new AppException(ucfirst($etiqueta) . ' debe tener entre ' . $minimo . ' y ' . $maximo . ' caracteres.');
        }

        return $valor;
    }

    public static function textoMultilinea(string $valor, string $etiqueta, int $maximo): string
    {
        $valor = str_replace(["\r\n", "\r"], "\n", strip_tags($valor));
        $lineas = [];
        foreach (explode("\n", $valor) as $linea) {
            $linea = trim(preg_replace('/[^\S\n]+/u', ' ', $linea) ?? '');
            if ($linea !== '') {
                $lineas[] = $linea;
            }
        }
        $texto = implode("\n", $lineas);
        if (mb_strlen($texto) > $maximo) {
            throw new AppException(ucfirst($etiqueta) . ' supera los ' . $maximo . ' caracteres.');
        }

        return $texto;
    }

    public static function conLetra(string $valor, string $etiqueta, int $minimo, int $maximo): string
    {
        $limpio = self::texto($valor, $etiqueta, $minimo, $maximo, true);
        if (!preg_match('/\p{L}/u', (string) $limpio)) {
            throw new AppException('Indique ' . $etiqueta . '.');
        }

        return (string) $limpio;
    }

    public static function persona(string $valor, string $etiqueta): string
    {
        $limpio = self::conLetra($valor, $etiqueta, 2, 120);
        if (!preg_match("/^[\p{L}\s.'-]{2,120}$/u", $limpio)) {
            throw new AppException(ucfirst($etiqueta) . ' contiene caracteres no permitidos.');
        }

        return $limpio;
    }

    public static function iniciales(string $valor): string
    {
        $valor = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($valor)) ?? '');
        $largo = strlen($valor);
        if ($largo < 2 || $largo > 10) {
            throw new AppException('Las iniciales deben tener entre 2 y 10 letras o números.');
        }

        return $valor;
    }

    public static function documento(string $valor): string
    {
        $valor = strtoupper(preg_replace('/\s+/', '', trim($valor)) ?? '');
        if (!preg_match('/^[A-Z0-9-]{4,30}$/', $valor)) {
            throw new AppException('El documento de identidad no es válido.');
        }

        return $valor;
    }

    public static function correo(?string $valor, bool $requerido): ?string
    {
        $valor = strtolower(trim((string) $valor));
        if ($valor === '') {
            if ($requerido) {
                throw new AppException('Indique el correo electrónico.');
            }

            return null;
        }
        if (strlen($valor) > 150 || !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            throw new AppException('El correo electrónico no es válido.');
        }

        return $valor;
    }

    public static function telefono(?string $valor): ?string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }
        if (!preg_match('/^[0-9+\-\s()]{7,30}$/', $valor)) {
            throw new AppException('El teléfono no es válido.');
        }

        return $valor;
    }

    public static function usuario(string $valor): string
    {
        $valor = strtolower(trim($valor));
        if (!preg_match('/^[a-z0-9._-]{4,50}$/', $valor)) {
            throw new AppException('El usuario debe tener entre 4 y 50 caracteres: letras, números, punto o guion.');
        }

        return $valor;
    }

    public static function clave(string $valor): string
    {
        $largo = strlen($valor);
        if ($largo < 8 || $largo > 72) {
            throw new AppException('La clave debe tener entre 8 y 72 caracteres.');
        }

        return $valor;
    }

    public static function fecha(?string $valor, string $etiqueta, bool $requerida): ?string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            if ($requerida) {
                throw new AppException('Indique ' . $etiqueta . '.');
            }

            return null;
        }
        $fecha = DateTime::createFromFormat('!Y-m-d', $valor);
        $errores = DateTime::getLastErrors();
        $avisos = is_array($errores) ? (int) ($errores['warning_count'] ?? 0) + (int) ($errores['error_count'] ?? 0) : 0;
        if (!$fecha || $avisos > 0 || $fecha->format('Y-m-d') !== $valor) {
            throw new AppException(ucfirst($etiqueta) . ' no es una fecha válida.');
        }

        return $fecha->format('Y-m-d');
    }

    public static function horas(mixed $valor): int
    {
        if ($valor === null || $valor === '' || !preg_match('/^\d+$/', (string) $valor)) {
            throw new AppException('Indique la duración en horas académicas.');
        }
        $horas = (int) $valor;
        if ($horas < 1 || $horas > 10000) {
            throw new AppException('La duración debe estar entre 1 y 10000 horas.');
        }

        return $horas;
    }

    public static function estadoBinario(string $valor, array $permitidos): string
    {
        if (!in_array($valor, $permitidos, true)) {
            throw new AppException('El estado no es válido.');
        }

        return $valor;
    }
}
