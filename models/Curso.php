<?php

declare(strict_types=1);

class Curso
{
    public static function porId(int $id): ?array
    {
        return fila('SELECT * FROM cursos WHERE id = ? LIMIT 1', 'i', [$id]);
    }

    public static function opciones(): array
    {
        return filas('SELECT id, nombre, codigo_curso, estado, iniciales FROM cursos ORDER BY nombre');
    }

    public static function incompletos(): array
    {
        return filas(
            "SELECT id, nombre, codigo_curso
             FROM cursos
             WHERE estado = 'activo'
               AND (
                    facilitador IS NULL OR facilitador = ''
                    OR institucion IS NULL OR institucion = ''
                    OR duracion_horas IS NULL OR duracion_horas = 0
                    OR fecha_inicio IS NULL OR fecha_fin IS NULL
               )
             ORDER BY nombre"
        );
    }

    public static function faltantes(array $curso): array
    {
        $faltan = [];
        if (empty($curso['duracion_horas'])) {
            $faltan[] = 'duración en horas';
        }
        if (empty($curso['fecha_inicio']) || empty($curso['fecha_fin'])) {
            $faltan[] = 'fechas de realización';
        }
        if (trim((string) ($curso['facilitador'] ?? '')) === '') {
            $faltan[] = 'facilitador';
        }
        if (trim((string) ($curso['institucion'] ?? '')) === '') {
            $faltan[] = 'institución emisora';
        }

        return $faltan;
    }

    public static function listar(string $busqueda, string $estado, int $pagina, int $porPagina = POR_PAGINA): array
    {
        [$where, $tipos, $params] = self::filtro($busqueda, $estado);
        $total = (int) escalar("SELECT COUNT(*) FROM cursos {$where}", $tipos, $params);
        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min(max(1, $pagina), $paginas);
        $limite = (int) $porPagina;
        $offset = ($pagina - 1) * $limite;
        $sql = "SELECT * FROM cursos {$where} ORDER BY nombre LIMIT {$limite} OFFSET {$offset}";

        return [
            'filas' => filas($sql, $tipos, $params),
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
        ];
    }

    public static function crear(array $entrada): string
    {
        $datos = self::validar($entrada, true);
        $db = db();
        for ($intento = 1; $intento <= 3; $intento++) {
            try {
                $db->begin_transaction();
                $codigo = Codigos::codigoCurso($datos['iniciales']);
                insertar(
                    'INSERT INTO cursos (
                        nombre, iniciales, codigo_curso, descripcion, objetivo, contenido_programatico,
                        fecha_inicio, fecha_fin, duracion_horas, facilitador, institucion, estado
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'activo\')',
                    'ssssssssiss',
                    [
                        $datos['nombre'],
                        $datos['iniciales'],
                        $codigo,
                        $datos['descripcion'],
                        $datos['objetivo'],
                        $datos['contenido'],
                        $datos['fecha_inicio'],
                        $datos['fecha_fin'],
                        $datos['duracion_horas'],
                        $datos['facilitador'],
                        $datos['institucion'],
                    ]
                );
                $db->commit();

                return $codigo;
            } catch (mysqli_sql_exception $e) {
                $db->rollback();
                if ((int) $e->getCode() === 1062 && $intento < 3) {
                    continue;
                }
                if ((int) $e->getCode() === 1062) {
                    throw new AppException('No se pudo asignar un código único al curso. Intente de nuevo.');
                }
                throw $e;
            } catch (Throwable $e) {
                $db->rollback();
                throw $e;
            }
        }

        throw new AppException('No se pudo registrar el curso.');
    }

    public static function actualizar(int $id, array $entrada): void
    {
        if (!self::porId($id)) {
            throw new AppException('Curso no encontrado.');
        }
        $datos = self::validar($entrada, false);
        ejecutar(
            'UPDATE cursos
             SET nombre = ?, descripcion = ?, objetivo = ?, contenido_programatico = ?,
                 fecha_inicio = ?, fecha_fin = ?, duracion_horas = ?, facilitador = ?, institucion = ?
             WHERE id = ?',
            'ssssssissi',
            [
                $datos['nombre'],
                $datos['descripcion'],
                $datos['objetivo'],
                $datos['contenido'],
                $datos['fecha_inicio'],
                $datos['fecha_fin'],
                $datos['duracion_horas'],
                $datos['facilitador'],
                $datos['institucion'],
                $id,
            ]
        );
    }

    public static function cambiarEstado(int $id, string $estado): void
    {
        $estado = Validador::estadoBinario($estado, ['activo', 'inactivo']);
        if (!self::porId($id)) {
            throw new AppException('Curso no encontrado.');
        }
        ejecutar('UPDATE cursos SET estado = ? WHERE id = ?', 'si', [$estado, $id]);
    }

    private static function validar(array $entrada, bool $creando): array
    {
        $inicio = Validador::fecha((string) ($entrada['fecha_inicio'] ?? ''), 'la fecha de inicio', true);
        $fin = Validador::fecha((string) ($entrada['fecha_fin'] ?? ''), 'la fecha de culminación', true);
        if ($inicio !== null && $fin !== null && $fin < $inicio) {
            throw new AppException('La fecha de culminación no puede ser anterior a la de inicio.');
        }

        return [
            'nombre' => Validador::conLetra((string) ($entrada['nombre'] ?? ''), 'el nombre del curso', 3, 200),
            'iniciales' => $creando ? Validador::iniciales((string) ($entrada['iniciales'] ?? '')) : '',
            'descripcion' => Validador::texto((string) ($entrada['descripcion'] ?? ''), 'la descripción', 0, 5000, false),
            'objetivo' => Validador::texto((string) ($entrada['objetivo'] ?? ''), 'el objetivo', 0, 5000, false),
            'contenido' => self::contenidoDesdeEntrada($entrada),
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'duracion_horas' => Validador::horas($entrada['duracion_horas'] ?? ''),
            'facilitador' => Validador::conLetra((string) ($entrada['facilitador'] ?? ''), 'el facilitador', 3, 150),
            'institucion' => Validador::conLetra((string) ($entrada['institucion'] ?? ''), 'la institución emisora', 3, 200),
        ];
    }

    private static function contenidoDesdeEntrada(array $entrada): ?string
    {
        if (!array_key_exists('cantidad_modulos', $entrada) && !isset($entrada['modulos_titulo'])) {
            return Validador::texto((string) ($entrada['contenido_programatico'] ?? ''), 'el contenido programático', 0, 20000, false);
        }

        $cantidad = (int) ($entrada['cantidad_modulos'] ?? 0);
        if ($cantidad < 0 || $cantidad > 12) {
            throw new AppException('La cantidad de módulos debe estar entre 0 y 12.');
        }
        $titulos = $entrada['modulos_titulo'] ?? [];
        $contenidos = $entrada['modulos_contenido'] ?? [];
        if (!is_array($titulos) || !is_array($contenidos)) {
            throw new AppException('El contenido de los módulos no es válido.');
        }

        $modulos = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $modulos[] = [
                'titulo' => Validador::conLetra((string) ($titulos[$i] ?? ''), 'el título del módulo ' . ($i + 1), 2, 180),
                'contenido' => Validador::textoMultilinea((string) ($contenidos[$i] ?? ''), 'el contenido del módulo ' . ($i + 1), 3000),
            ];
        }
        $texto = MallaParser::componer($modulos);
        if (mb_strlen($texto) > 20000) {
            throw new AppException('El contenido programático supera el tamaño permitido.');
        }

        return $texto === '' ? null : $texto;
    }

    private static function filtro(string $busqueda, string $estado): array
    {
        $busqueda = trim($busqueda);
        $estado = in_array($estado, ['activo', 'inactivo'], true) ? $estado : '';
        $clausulas = [];
        $tipos = '';
        $params = [];
        if ($busqueda !== '') {
            $like = comodin($busqueda);
            $clausulas[] = "(nombre LIKE ? OR codigo_curso LIKE ? OR iniciales LIKE ? OR facilitador LIKE ? OR institucion LIKE ?)";
            $tipos .= 'sssss';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($estado !== '') {
            $clausulas[] = 'estado = ?';
            $tipos .= 's';
            $params[] = $estado;
        }
        $where = $clausulas === [] ? '' : 'WHERE ' . implode(' AND ', $clausulas);

        return [$where, $tipos, $params];
    }
}
