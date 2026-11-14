<?php

declare(strict_types=1);

class Inscripcion
{
    public static function crear(int $estudianteId, int $cursoId, string $fecha): void
    {
        $fecha = Validador::fecha($fecha, 'la fecha de inscripción', true);
        $curso = Curso::porId($cursoId);
        if (!$curso) {
            throw new AppException('Seleccione un curso.');
        }
        if ($curso['estado'] !== 'activo') {
            throw new AppException('No se puede inscribir estudiantes en un curso inactivo.');
        }
        if (!Estudiante::porId($estudianteId)) {
            throw new AppException('Seleccione un estudiante registrado.');
        }
        try {
            insertar(
                "INSERT INTO inscripciones (id_estudiante, id_curso, fecha_inscripcion, estado) VALUES (?, ?, ?, 'activa')",
                'iis',
                [$estudianteId, $cursoId, $fecha]
            );
        } catch (mysqli_sql_exception $e) {
            if ((int) $e->getCode() === 1062) {
                throw new AppException('Este estudiante ya está inscrito en el curso.');
            }
            throw $e;
        }
    }

    public static function resumen(int $cursoId): array
    {
        $total = (int) escalar(
            "SELECT COUNT(*) FROM inscripciones WHERE id_curso = ? AND estado = 'activa'",
            'i',
            [$cursoId]
        );
        $conCertificado = (int) escalar(
            "SELECT COUNT(*)
             FROM inscripciones i
             WHERE i.id_curso = ? AND i.estado = 'activa'
               AND EXISTS (
                    SELECT 1 FROM certificados c
                    WHERE c.id_inscripcion = i.id AND c.estado = 'activo'
               )",
            'i',
            [$cursoId]
        );

        return [
            'total' => $total,
            'con_certificado' => $conCertificado,
            'sin_certificado' => $total - $conCertificado,
        ];
    }

    public static function listarPorCurso(int $cursoId, string $busqueda, int $pagina, int $porPagina = POR_PAGINA): array
    {
        $busqueda = trim($busqueda);
        $tipos = 'i';
        $params = [$cursoId];
        $extra = '';
        if ($busqueda !== '') {
            $like = comodin($busqueda);
            $extra = " AND (e.nombres LIKE ? OR e.apellidos LIKE ? OR e.documento LIKE ? OR CONCAT(e.nombres, ' ', e.apellidos) LIKE ?)";
            $tipos .= 'ssss';
            array_push($params, $like, $like, $like, $like);
        }
        $joins = "FROM inscripciones i
                  INNER JOIN estudiantes e ON e.id = i.id_estudiante
                  LEFT JOIN certificados cact ON cact.id_inscripcion = i.id AND cact.estado = 'activo'
                  WHERE i.id_curso = ? AND i.estado = 'activa' {$extra}";
        $total = (int) escalar("SELECT COUNT(DISTINCT i.id) {$joins}", $tipos, $params);
        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min(max(1, $pagina), $paginas);
        $limite = (int) $porPagina;
        $offset = ($pagina - 1) * $limite;
        $sql = "SELECT i.id, i.fecha_inscripcion, e.nombres, e.apellidos, e.documento,
                       cact.id AS certificado_id, cact.codigo_certificado,
                       (SELECT COUNT(*) FROM certificados ca WHERE ca.id_inscripcion = i.id AND ca.estado = 'anulado') AS anulados
                {$joins}
                ORDER BY e.apellidos, e.nombres
                LIMIT {$limite} OFFSET {$offset}";

        return [
            'filas' => filas($sql, $tipos, $params),
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
        ];
    }
}
