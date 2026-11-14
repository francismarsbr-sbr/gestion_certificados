<?php

declare(strict_types=1);

/**
 * Composición de códigos del sistema.
 * Para cambiar la regla institucional, modifique solo los métodos componer*.
 *
 * Curso:        IAP-001
 * Certificado:  IAP-001-0001
 */
class Codigos
{
    public static function codigoCurso(string $iniciales): string
    {
        $iniciales = self::normalizarIniciales($iniciales);
        $consecutivo = self::ultimoConsecutivo('cursos', $iniciales) + 1;

        return self::componerCodigoCurso($iniciales, $consecutivo);
    }

    public static function codigoCertificado(string $codigoCurso): string
    {
        $codigoCurso = strtoupper(trim($codigoCurso));
        if ($codigoCurso === '') {
            throw new AppException('El curso no tiene código.');
        }
        $consecutivo = self::ultimoConsecutivo('certificados', $codigoCurso) + 1;

        return self::componerCodigoCertificado($codigoCurso, $consecutivo);
    }

    public static function tokenVerificacion(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function archivoPdf(): string
    {
        return 'cert_' . bin2hex(random_bytes(16)) . '.pdf';
    }

    public static function normalizarIniciales(string $iniciales): string
    {
        $iniciales = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $iniciales) ?? '');
        if ($iniciales === '') {
            throw new AppException('Las iniciales del curso no son válidas.');
        }

        return $iniciales;
    }

    private static function componerCodigoCurso(string $iniciales, int $consecutivo): string
    {
        return sprintf('%s-%03d', $iniciales, $consecutivo);
    }

    private static function componerCodigoCertificado(string $codigoCurso, int $consecutivo): string
    {
        return sprintf('%s-%04d', $codigoCurso, $consecutivo);
    }

    private static function ultimoConsecutivo(string $origen, string $prefijo): int
    {
        if ($origen === 'cursos') {
            $sql = 'SELECT codigo_curso AS codigo FROM cursos WHERE codigo_curso LIKE ? FOR UPDATE';
        } elseif ($origen === 'certificados') {
            $sql = 'SELECT codigo_certificado AS codigo FROM certificados WHERE codigo_certificado LIKE ? FOR UPDATE';
        } else {
            throw new AppException('No se pudo generar el código.');
        }

        $stmt = db()->prepare($sql);
        $like = $prefijo . '-%';
        enlazar($stmt, 's', [$like]);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $patron = '/^' . preg_quote($prefijo, '/') . '-(\d+)$/';
        $maximo = 0;
        while ($fila = $resultado->fetch_assoc()) {
            if (preg_match($patron, (string) $fila['codigo'], $coincidencia)) {
                $maximo = max($maximo, (int) $coincidencia[1]);
            }
        }
        $stmt->close();

        return $maximo;
    }
}
