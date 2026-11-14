<?php

declare(strict_types=1);

class Usuario
{
    public static function porId(int $id): ?array
    {
        return fila(
            'SELECT u.id, u.nombre, u.apellido, u.usuario, u.correo, u.password, u.id_rol, u.estado, u.fecha_registro, r.nombre AS rol
             FROM usuarios u
             INNER JOIN roles r ON r.id = u.id_rol
             WHERE u.id = ?
             LIMIT 1',
            'i',
            [$id]
        );
    }

    public static function porUsuario(string $usuario): ?array
    {
        return fila(
            'SELECT u.id, u.nombre, u.apellido, u.usuario, u.correo, u.password, u.id_rol, u.estado, u.fecha_registro, r.nombre AS rol
             FROM usuarios u
             INNER JOIN roles r ON r.id = u.id_rol
             WHERE u.usuario = ?
             LIMIT 1',
            's',
            [$usuario]
        );
    }

    public static function actualizarHash(int $id, string $hash): void
    {
        ejecutar('UPDATE usuarios SET password = ? WHERE id = ?', 'si', [$hash, $id]);
    }

    public static function listar(string $busqueda, int $pagina, int $porPagina = POR_PAGINA): array
    {
        [$where, $tipos, $params] = self::filtro($busqueda);
        $total = (int) escalar("SELECT COUNT(*) FROM usuarios u {$where}", $tipos, $params);
        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min(max(1, $pagina), $paginas);
        $limite = (int) $porPagina;
        $offset = ($pagina - 1) * $limite;
        $sql = "SELECT u.id, u.nombre, u.apellido, u.usuario, u.correo, u.estado, u.fecha_registro, r.nombre AS rol
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.id_rol
                {$where}
                ORDER BY u.nombre, u.apellido
                LIMIT {$limite} OFFSET {$offset}";

        return [
            'filas' => filas($sql, $tipos, $params),
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
        ];
    }

    public static function hayAdministradorActivo(): bool
    {
        return (int) escalar(
            "SELECT COUNT(*)
             FROM usuarios u
             INNER JOIN roles r ON r.id = u.id_rol
             WHERE r.nombre = 'administrador' AND u.estado = 'activo'"
        ) > 0;
    }

    public static function registrarCuenta(array $entrada): array
    {
        $db = db();
        $db->begin_transaction();
        try {
            $rolAdmin = fila("SELECT id FROM roles WHERE nombre = 'administrador' LIMIT 1 FOR UPDATE");
            if (!$rolAdmin) {
                throw new AppException('El rol de administrador no está configurado.');
            }
            $rol = self::hayAdministradorActivo() ? 'operador' : 'administrador';
            $entrada['rol'] = $rol;
            $entrada['estado'] = 'activo';
            $datos = self::validar($entrada, true);
            $id = insertar(
                'INSERT INTO usuarios (nombre, apellido, usuario, correo, password, id_rol, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                'sssssis',
                [
                    $datos['nombre'],
                    $datos['apellido'],
                    $datos['usuario'],
                    $datos['correo'],
                    password_hash($datos['clave'], PASSWORD_DEFAULT),
                    self::idRol($datos['rol']),
                    'activo',
                ]
            );
            $db->commit();

            return [
                'id' => $id,
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'usuario' => $datos['usuario'],
                'rol' => $rol,
            ];
        } catch (mysqli_sql_exception $e) {
            $db->rollback();
            traducir_duplicado($e, [
                'uq_usuarios_correo' => 'Ese correo ya está registrado.',
                'correo' => 'Ese correo ya está registrado.',
                'uq_usuarios_usuario' => 'Ese nombre de usuario ya está registrado.',
                'usuario' => 'Ese nombre de usuario ya está registrado.',
            ]);
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }

        throw new AppException('No se pudo crear la cuenta.');
    }

    public static function crear(array $entrada): void
    {
        $datos = self::validar($entrada, true);
        try {
            insertar(
                'INSERT INTO usuarios (nombre, apellido, usuario, correo, password, id_rol, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                'sssssis',
                [
                    $datos['nombre'],
                    $datos['apellido'],
                    $datos['usuario'],
                    $datos['correo'],
                    password_hash($datos['clave'], PASSWORD_DEFAULT),
                    self::idRol($datos['rol']),
                    $datos['estado'],
                ]
            );
        } catch (mysqli_sql_exception $e) {
            traducir_duplicado($e, [
                'uq_usuarios_correo' => 'Ese correo ya está registrado.',
                'correo' => 'Ese correo ya está registrado.',
                'uq_usuarios_usuario' => 'Ese nombre de usuario ya está registrado.',
                'usuario' => 'Ese nombre de usuario ya está registrado.',
            ]);
        }
    }

    public static function actualizar(int $id, array $entrada): void
    {
        $actual = self::porId($id);
        if (!$actual) {
            throw new AppException('Usuario no encontrado.');
        }
        $datos = self::validar($entrada, false);
        if ($id === (int) ($_SESSION['usuario_id'] ?? 0) && $datos['estado'] !== 'activo') {
            throw new AppException('No puede desactivar su propio usuario.');
        }
        self::protegerUltimoAdmin($actual, $datos['rol'], $datos['estado']);
        try {
            ejecutar(
                'UPDATE usuarios
                 SET nombre = ?, apellido = ?, usuario = ?, correo = ?, id_rol = ?, estado = ?
                 WHERE id = ?',
                'ssssisi',
                [
                    $datos['nombre'],
                    $datos['apellido'],
                    $datos['usuario'],
                    $datos['correo'],
                    self::idRol($datos['rol']),
                    $datos['estado'],
                    $id,
                ]
            );
        } catch (mysqli_sql_exception $e) {
            traducir_duplicado($e, [
                'uq_usuarios_correo' => 'Ese correo ya está registrado.',
                'correo' => 'Ese correo ya está registrado.',
                'uq_usuarios_usuario' => 'Ese nombre de usuario ya está registrado.',
                'usuario' => 'Ese nombre de usuario ya está registrado.',
            ]);
        }
        if ($datos['clave'] !== '') {
            self::actualizarHash($id, password_hash($datos['clave'], PASSWORD_DEFAULT));
        }
    }

    public static function cambiarEstado(int $id, string $estado): void
    {
        $estado = Validador::estadoBinario($estado, ['activo', 'inactivo']);
        $actual = self::porId($id);
        if (!$actual) {
            throw new AppException('Usuario no encontrado.');
        }
        if ($id === (int) ($_SESSION['usuario_id'] ?? 0) && $estado !== 'activo') {
            throw new AppException('No puede desactivar su propio usuario.');
        }
        self::protegerUltimoAdmin($actual, (string) $actual['rol'], $estado);
        ejecutar('UPDATE usuarios SET estado = ? WHERE id = ?', 'si', [$estado, $id]);
    }

    public static function cambiarClave(int $id, string $actual, string $nueva, string $confirmacion): void
    {
        $registro = self::porId($id);
        if (!$registro || !password_verify($actual, (string) $registro['password'])) {
            throw new AppException('La clave actual no coincide.');
        }
        if ($nueva !== $confirmacion) {
            throw new AppException('La confirmación de la clave no coincide.');
        }
        if ($nueva === $actual) {
            throw new AppException('La clave nueva debe ser distinta a la actual.');
        }
        Validador::clave($nueva);
        self::actualizarHash($id, password_hash($nueva, PASSWORD_DEFAULT));
    }

    private static function validar(array $entrada, bool $creando): array
    {
        $clave = (string) ($entrada['clave'] ?? '');
        $confirmacion = (string) ($entrada['clave_confirmacion'] ?? '');
        if ($creando || $clave !== '' || $confirmacion !== '') {
            if ($clave !== $confirmacion) {
                throw new AppException('La confirmación de la clave no coincide.');
            }
            Validador::clave($clave);
        }

        return [
            'nombre' => Validador::persona((string) ($entrada['nombre'] ?? ''), 'el nombre'),
            'apellido' => Validador::persona((string) ($entrada['apellido'] ?? ''), 'el apellido'),
            'usuario' => Validador::usuario((string) ($entrada['usuario'] ?? '')),
            'correo' => (string) Validador::correo((string) ($entrada['correo'] ?? ''), true),
            'rol' => self::rolNombre((string) ($entrada['rol'] ?? '')),
            'estado' => Validador::estadoBinario((string) ($entrada['estado'] ?? 'activo'), ['activo', 'inactivo']),
            'clave' => $clave,
        ];
    }

    private static function rolNombre(string $rol): string
    {
        if (!in_array($rol, ['administrador', 'operador'], true)) {
            throw new AppException('El rol no es válido.');
        }

        return $rol;
    }

    private static function idRol(string $rol): int
    {
        $id = escalar('SELECT id FROM roles WHERE nombre = ? LIMIT 1', 's', [$rol]);
        if (!$id) {
            throw new AppException('El rol no está configurado en la base de datos.');
        }

        return (int) $id;
    }

    private static function protegerUltimoAdmin(array $actual, string $rolNuevo, string $estadoNuevo): void
    {
        $eraAdminActivo = $actual['rol'] === 'administrador' && $actual['estado'] === 'activo';
        $sigue = $rolNuevo === 'administrador' && $estadoNuevo === 'activo';
        if (!$eraAdminActivo || $sigue) {
            return;
        }
        $otros = (int) escalar(
            "SELECT COUNT(*)
             FROM usuarios u
             INNER JOIN roles r ON r.id = u.id_rol
             WHERE r.nombre = 'administrador' AND u.estado = 'activo' AND u.id <> ?",
            'i',
            [(int) $actual['id']]
        );
        if ($otros < 1) {
            throw new AppException('Debe permanecer al menos un administrador activo.');
        }
    }

    private static function filtro(string $busqueda): array
    {
        $busqueda = trim($busqueda);
        if ($busqueda === '') {
            return ['', '', []];
        }
        $like = comodin($busqueda);

        return [
            "WHERE u.nombre LIKE ? OR u.apellido LIKE ? OR u.usuario LIKE ? OR u.correo LIKE ? OR CONCAT(u.nombre, ' ', u.apellido) LIKE ?",
            'sssss',
            [$like, $like, $like, $like, $like],
        ];
    }
}
