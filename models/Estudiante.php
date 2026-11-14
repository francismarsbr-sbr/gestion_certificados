<?php

declare(strict_types=1);

class Estudiante
{
    public static function porId(int $id): ?array
    {
        return fila('SELECT * FROM estudiantes WHERE id = ? LIMIT 1', 'i', [$id]);
    }

    public static function porDocumento(string $documento): ?array
    {
        return fila('SELECT * FROM estudiantes WHERE documento = ? LIMIT 1', 's', [$documento]);
    }

    public static function listar(string $busqueda, int $pagina, int $porPagina = POR_PAGINA): array
    {
        [$where, $tipos, $params] = self::filtro($busqueda);
        $total = (int) escalar("SELECT COUNT(*) FROM estudiantes {$where}", $tipos, $params);
        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min(max(1, $pagina), $paginas);
        $limite = (int) $porPagina;
        $offset = ($pagina - 1) * $limite;
        $sql = "SELECT * FROM estudiantes {$where} ORDER BY apellidos, nombres LIMIT {$limite} OFFSET {$offset}";

        return [
            'filas' => filas($sql, $tipos, $params),
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
        ];
    }

    public static function buscarParaCurso(string $busqueda, int $cursoId): array
    {
        $busqueda = trim($busqueda);
        if ($busqueda === '' || $cursoId <= 0) {
            return [];
        }
        $like = comodin($busqueda);

        return filas(
            "SELECT e.id, e.nombres, e.apellidos, e.documento, e.correo,
                    (SELECT i.id FROM inscripciones i WHERE i.id_estudiante = e.id AND i.id_curso = ? LIMIT 1) AS inscripcion_id
             FROM estudiantes e
             WHERE e.nombres LIKE ? OR e.apellidos LIKE ? OR e.documento LIKE ?
                OR CONCAT(e.nombres, ' ', e.apellidos) LIKE ?
             ORDER BY e.apellidos, e.nombres
             LIMIT 15",
            'issss',
            [$cursoId, $like, $like, $like, $like]
        );
    }

    public static function crear(array $entrada): int
    {
        $datos = self::validar($entrada);
        try {
            return insertar(
                'INSERT INTO estudiantes (nombres, apellidos, documento, correo, telefono, institucion)
                 VALUES (?, ?, ?, ?, ?, ?)',
                'ssssss',
                array_values($datos)
            );
        } catch (mysqli_sql_exception $e) {
            traducir_duplicado($e, [
                'uq_estudiantes_documento' => 'Ya existe un estudiante con ese documento de identidad.',
                'documento' => 'Ya existe un estudiante con ese documento de identidad.',
            ]);
        }

        return 0;
    }

    public static function actualizar(int $id, array $entrada): void
    {
        if (!self::porId($id)) {
            throw new AppException('Estudiante no encontrado.');
        }
        $datos = self::validar($entrada);
        try {
            ejecutar(
                'UPDATE estudiantes
                 SET nombres = ?, apellidos = ?, documento = ?, correo = ?, telefono = ?, institucion = ?
                 WHERE id = ?',
                'ssssssi',
                [...array_values($datos), $id]
            );
        } catch (mysqli_sql_exception $e) {
            traducir_duplicado($e, [
                'uq_estudiantes_documento' => 'Ya existe un estudiante con ese documento de identidad.',
                'documento' => 'Ya existe un estudiante con ese documento de identidad.',
            ]);
        }
    }

    private static function validar(array $entrada): array
    {
        return [
            'nombres' => Validador::persona((string) ($entrada['nombres'] ?? ''), 'los nombres'),
            'apellidos' => Validador::persona((string) ($entrada['apellidos'] ?? ''), 'los apellidos'),
            'documento' => Validador::documento((string) ($entrada['documento'] ?? '')),
            'correo' => Validador::correo((string) ($entrada['correo'] ?? ''), false),
            'telefono' => Validador::telefono((string) ($entrada['telefono'] ?? '')),
            'institucion' => Validador::texto((string) ($entrada['institucion'] ?? ''), 'la institución', 2, 200, false),
        ];
    }

    private static function filtro(string $busqueda): array
    {
        $busqueda = trim($busqueda);
        if ($busqueda === '') {
            return ['', '', []];
        }
        $like = comodin($busqueda);

        return [
            "WHERE nombres LIKE ? OR apellidos LIKE ? OR documento LIKE ? OR CONCAT(nombres, ' ', apellidos) LIKE ?",
            'ssss',
            [$like, $like, $like, $like],
        ];
    }
}
