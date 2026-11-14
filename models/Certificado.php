<?php

declare(strict_types=1);

class Certificado
{
    public static function generar(int $idInscripcion): array
    {
        if ($idInscripcion <= 0) {
            throw new AppException('Seleccione una inscripción.');
        }
        $db = db();
        for ($intento = 1; $intento <= 3; $intento++) {
            $archivo = null;
            try {
                $db->begin_transaction();
                $bloqueo = fila(
                    'SELECT id, id_curso, estado FROM inscripciones WHERE id = ? LIMIT 1 FOR UPDATE',
                    'i',
                    [$idInscripcion]
                );
                if (!$bloqueo) {
                    throw new AppException('No se encontró la inscripción.');
                }
                $fila = self::datosInscripcion($idInscripcion);
                if (!$fila || $fila['estado_inscripcion'] !== 'activa') {
                    throw new AppException('La inscripción no está activa.');
                }
                if ($fila['estado_curso'] !== 'activo') {
                    throw new AppException('El curso está inactivo. Un administrador debe activarlo antes de emitir certificados.');
                }
                $faltan = Curso::faltantes([
                    'duracion_horas' => $fila['duracion_horas'],
                    'fecha_inicio' => $fila['fecha_inicio'],
                    'fecha_fin' => $fila['fecha_fin'],
                    'facilitador' => $fila['facilitador'],
                    'institucion' => $fila['institucion_emisora'],
                ]);
                if ($faltan !== []) {
                    throw new AppException('Antes de emitir el certificado complete en el curso: ' . implode(', ', $faltan) . '.');
                }
                $activo = fila(
                    "SELECT id, codigo_certificado FROM certificados WHERE id_inscripcion = ? AND estado = 'activo' LIMIT 1 FOR UPDATE",
                    'i',
                    [$idInscripcion]
                );
                if ($activo) {
                    throw new AppException('Ya existe un certificado activo (' . $activo['codigo_certificado'] . '). Puede descargarlo.');
                }

                $codigo = Codigos::codigoCertificado((string) $fila['codigo_curso']);
                $token = Codigos::tokenVerificacion();
                $fecha = date('Y-m-d');
                $id = insertar(
                    "INSERT INTO certificados (id_inscripcion, codigo_certificado, token_verificacion, fecha_emision, ruta_pdf, estado)
                     VALUES (?, ?, ?, ?, NULL, 'activo')",
                    'isss',
                    [$idInscripcion, $codigo, $token, $fecha]
                );
                $nombre = Codigos::archivoPdf();
                $directorio = RAIZ . '/uploads/certificados';
                if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
                    throw new AppException('No se pudo crear la carpeta de certificados.');
                }
                $archivo = $directorio . DIRECTORY_SEPARATOR . $nombre;
                $fila['codigo_certificado'] = $codigo;
                $fila['token_verificacion'] = $token;
                $fila['fecha_emision'] = $fecha;
                $fila['estado'] = 'activo';
                CertificadoPdf::crear($fila, $archivo);
                ejecutar(
                    'UPDATE certificados SET ruta_pdf = ? WHERE id = ?',
                    'si',
                    ['uploads/certificados/' . $nombre, $id]
                );
                $db->commit();

                return [
                    'id' => $id,
                    'codigo' => $codigo,
                    'id_curso' => (int) $fila['id_curso'],
                ];
            } catch (mysqli_sql_exception $e) {
                self::revertir($db, $archivo);
                if ((int) $e->getCode() === 1062 && $intento < 3) {
                    continue;
                }
                if ((int) $e->getCode() === 1062) {
                    throw new AppException('No se pudo asignar un código único. Intente de nuevo.');
                }
                throw $e;
            } catch (Throwable $e) {
                self::revertir($db, $archivo);
                throw $e;
            }
        }

        throw new AppException('No se pudo generar el certificado.');
    }

    public static function anular(int $id): array
    {
        if (!Auth::esAdmin()) {
            throw new AppException('Solo un administrador puede anular certificados.');
        }
        $db = db();
        $temporal = null;
        try {
            $db->begin_transaction();
            $bloqueo = fila('SELECT id, estado, ruta_pdf FROM certificados WHERE id = ? LIMIT 1 FOR UPDATE', 'i', [$id]);
            if (!$bloqueo) {
                throw new AppException('Certificado no encontrado.');
            }
            if ($bloqueo['estado'] !== 'activo') {
                throw new AppException('El certificado ya está anulado.');
            }
            $fila = self::porIdCompleto($id);
            if (!$fila) {
                throw new AppException('Certificado no encontrado.');
            }
            $destino = self::rutaAbsoluta((string) $fila['ruta_pdf']);
            $temporal = $destino . '.tmp';
            $fila['estado'] = 'anulado';
            CertificadoPdf::crear($fila, $temporal);
            ejecutar("UPDATE certificados SET estado = 'anulado', fecha_anulacion = NOW() WHERE id = ?", 'i', [$id]);
            $db->commit();
            if (!copy($temporal, $destino)) {
                registrar_error(new RuntimeException('No se pudo reemplazar el PDF anulado.'));
            }
            if (is_file($temporal)) {
                unlink($temporal);
            }

            return [
                'id_curso' => (int) $fila['id_curso'],
                'codigo' => (string) $fila['codigo_certificado'],
            ];
        } catch (Throwable $e) {
            self::revertir($db, null);
            if ($temporal && is_file($temporal)) {
                unlink($temporal);
            }
            throw $e;
        }
    }

    public static function descargar(int $id): void
    {
        try {
            $fila = self::porIdCompleto($id);
            if (!$fila || trim((string) ($fila['ruta_pdf'] ?? '')) === '') {
                self::noEncontrado();
            }
            $absoluta = self::rutaAbsoluta((string) $fila['ruta_pdf']);
            if (!is_file($absoluta)) {
                CertificadoPdf::crear($fila, $absoluta);
            }
            $real = realpath($absoluta);
            $base = realpath(RAIZ . '/uploads/certificados');
            if ($real === false || $base === false) {
                self::noEncontrado();
            }
            $realNormal = strtolower(str_replace('\\', '/', $real));
            $baseNormal = rtrim(strtolower(str_replace('\\', '/', $base)), '/') . '/';
            if (!str_starts_with($realNormal, $baseNormal) || !str_ends_with($realNormal, '.pdf')) {
                self::noEncontrado();
            }
            $mime = mime_content_type($real);
            if ($mime !== 'application/pdf') {
                self::noEncontrado();
            }
            $nombre = preg_replace('/[^A-Za-z0-9._-]/', '', (string) $fila['codigo_certificado']) ?: 'certificado';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="certificado-' . $nombre . '.pdf"');
            header('Content-Length: ' . (string) filesize($real));
            header('Cache-Control: private, no-store');
            header('X-Content-Type-Options: nosniff');
            readfile($real);
            exit;
        } catch (Throwable $e) {
            registrar_error($e);
            self::noEncontrado();
        }
    }

    public static function porToken(string $token): ?array
    {
        return fila(
            "SELECT ce.codigo_certificado, ce.fecha_emision, ce.estado, ce.fecha_anulacion,
                    e.nombres, e.apellidos,
                    c.nombre AS curso, c.duracion_horas, c.institucion
             FROM certificados ce
             INNER JOIN inscripciones i ON i.id = ce.id_inscripcion
             INNER JOIN estudiantes e ON e.id = i.id_estudiante
             INNER JOIN cursos c ON c.id = i.id_curso
             WHERE ce.token_verificacion = ?
             LIMIT 1",
            's',
            [$token]
        );
    }

    public static function listar(string $busqueda, string $estado, int $cursoId, int $pagina, int $porPagina = POR_PAGINA): array
    {
        $clausulas = [];
        $tipos = '';
        $params = [];
        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            $like = comodin($busqueda);
            $clausulas[] = "(ce.codigo_certificado LIKE ? OR e.nombres LIKE ? OR e.apellidos LIKE ? OR c.nombre LIKE ? OR CONCAT(e.nombres, ' ', e.apellidos) LIKE ?)";
            $tipos .= 'sssss';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if (in_array($estado, ['activo', 'anulado'], true)) {
            $clausulas[] = 'ce.estado = ?';
            $tipos .= 's';
            $params[] = $estado;
        }
        if ($cursoId > 0) {
            $clausulas[] = 'c.id = ?';
            $tipos .= 'i';
            $params[] = $cursoId;
        }
        $where = $clausulas === [] ? '' : 'WHERE ' . implode(' AND ', $clausulas);
        $desde = "FROM certificados ce
                  INNER JOIN inscripciones i ON i.id = ce.id_inscripcion
                  INNER JOIN estudiantes e ON e.id = i.id_estudiante
                  INNER JOIN cursos c ON c.id = i.id_curso
                  {$where}";
        $total = (int) escalar("SELECT COUNT(*) {$desde}", $tipos, $params);
        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min(max(1, $pagina), $paginas);
        $limite = (int) $porPagina;
        $offset = ($pagina - 1) * $limite;
        $sql = "SELECT ce.id, ce.codigo_certificado, ce.fecha_emision, ce.estado, ce.ruta_pdf,
                       e.nombres, e.apellidos, c.nombre AS curso, c.id AS id_curso
                {$desde}
                ORDER BY ce.id DESC
                LIMIT {$limite} OFFSET {$offset}";

        return [
            'filas' => filas($sql, $tipos, $params),
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
        ];
    }

    public static function recientes(int $limite = 6): array
    {
        $limite = max(1, min(20, $limite));

        return filas(
            "SELECT ce.codigo_certificado, ce.fecha_emision, ce.estado, e.nombres, e.apellidos, c.nombre AS curso
             FROM certificados ce
             INNER JOIN inscripciones i ON i.id = ce.id_inscripcion
             INNER JOIN estudiantes e ON e.id = i.id_estudiante
             INNER JOIN cursos c ON c.id = i.id_curso
             ORDER BY ce.id DESC
             LIMIT {$limite}"
        );
    }

    public static function sinCertificadoActivo(): int
    {
        return (int) escalar(
            "SELECT COUNT(*)
             FROM inscripciones i
             WHERE i.estado = 'activa'
               AND NOT EXISTS (
                    SELECT 1 FROM certificados c
                    WHERE c.id_inscripcion = i.id AND c.estado = 'activo'
               )"
        );
    }

    private static function datosInscripcion(int $id): ?array
    {
        return fila(
            "SELECT i.id, i.id_estudiante, i.id_curso, i.fecha_inscripcion, i.estado AS estado_inscripcion,
                    e.nombres, e.apellidos, e.documento,
                    c.nombre AS curso_nombre, c.codigo_curso, c.iniciales, c.duracion_horas,
                    c.fecha_inicio, c.fecha_fin, c.facilitador, c.institucion AS institucion_emisora,
                    c.objetivo, c.contenido_programatico, c.estado AS estado_curso
             FROM inscripciones i
             INNER JOIN estudiantes e ON e.id = i.id_estudiante
             INNER JOIN cursos c ON c.id = i.id_curso
             WHERE i.id = ?
             LIMIT 1",
            'i',
            [$id]
        );
    }

    private static function porIdCompleto(int $id): ?array
    {
        return fila(
            "SELECT ce.id, ce.codigo_certificado, ce.token_verificacion, ce.fecha_emision, ce.ruta_pdf, ce.estado,
                    i.id AS inscripcion_id, i.id_curso, i.estado AS estado_inscripcion,
                    e.nombres, e.apellidos, e.documento,
                    c.nombre AS curso_nombre, c.codigo_curso, c.iniciales, c.duracion_horas,
                    c.fecha_inicio, c.fecha_fin, c.facilitador, c.institucion AS institucion_emisora,
                    c.objetivo, c.contenido_programatico, c.estado AS estado_curso
             FROM certificados ce
             INNER JOIN inscripciones i ON i.id = ce.id_inscripcion
             INNER JOIN estudiantes e ON e.id = i.id_estudiante
             INNER JOIN cursos c ON c.id = i.id_curso
             WHERE ce.id = ?
             LIMIT 1",
            'i',
            [$id]
        );
    }

    private static function rutaAbsoluta(string $relativa): string
    {
        $relativa = str_replace('\\', '/', $relativa);
        $relativa = ltrim($relativa, '/');
        if ($relativa === '' || str_contains($relativa, '..') || !str_starts_with($relativa, 'uploads/certificados/') || !str_ends_with(strtolower($relativa), '.pdf')) {
            throw new AppException('Ruta de certificado no válida.');
        }

        return RAIZ . '/' . $relativa;
    }

    private static function revertir(mysqli $db, ?string $archivo): void
    {
        try {
            $db->rollback();
        } catch (Throwable $ignorar) {
        }
        if ($archivo && is_file($archivo)) {
            unlink($archivo);
        }
    }

    private static function noEncontrado(): void
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'No se encontró el certificado.';
        exit;
    }
}
