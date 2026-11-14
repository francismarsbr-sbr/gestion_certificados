<?php

declare(strict_types=1);

class Auth
{
    public static function haySesion(): bool
    {
        return !empty($_SESSION['usuario_id']);
    }

    public static function actual(): array
    {
        return [
            'id' => (int) ($_SESSION['usuario_id'] ?? 0),
            'nombre' => (string) ($_SESSION['nombre'] ?? ''),
            'apellido' => (string) ($_SESSION['apellido'] ?? ''),
            'usuario' => (string) ($_SESSION['usuario'] ?? ''),
            'rol' => (string) ($_SESSION['rol'] ?? ''),
        ];
    }

    public static function esAdmin(): bool
    {
        return ($_SESSION['rol'] ?? '') === 'administrador';
    }

    public static function iniciar(string $usuario, string $clave): void
    {
        $ahora = time();
        $bloqueo = (int) ($_SESSION['bloqueo_hasta'] ?? 0);
        if ($bloqueo > $ahora) {
            $minutos = (int) ceil(($bloqueo - $ahora) / 60);
            throw new AppException('Demasiados intentos fallidos. Espere ' . max(1, $minutos) . ' minuto(s).');
        }

        $usuario = strtolower(trim($usuario));
        $registro = $usuario !== '' ? Usuario::porUsuario($usuario) : null;
        $hash = is_array($registro) ? (string) $registro['password'] : self::hashInvalido();
        $valida = password_verify($clave, $hash);

        if (!is_array($registro) || !$valida || $registro['estado'] !== 'activo') {
            $intentos = (int) ($_SESSION['intentos'] ?? 0) + 1;
            $_SESSION['intentos'] = $intentos;
            if ($intentos >= 5) {
                $_SESSION['bloqueo_hasta'] = $ahora + 300;
                $_SESSION['intentos'] = 0;
            }
            throw new AppException('Usuario o clave incorrectos.');
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            Usuario::actualizarHash((int) $registro['id'], password_hash($clave, PASSWORD_DEFAULT));
        }

        self::establecer($registro);
    }

    public static function establecer(array $registro): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int) $registro['id'];
        $_SESSION['nombre'] = (string) $registro['nombre'];
        $_SESSION['apellido'] = (string) $registro['apellido'];
        $_SESSION['usuario'] = (string) $registro['usuario'];
        $_SESSION['rol'] = (string) $registro['rol'];
        $_SESSION['ultimo_acceso'] = time();
        unset($_SESSION['intentos'], $_SESSION['bloqueo_hasta']);
    }

    public static function cerrar(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $parametros['path'],
                $parametros['domain'],
                (bool) $parametros['secure'],
                (bool) $parametros['httponly']
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function exigirLogin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            redirigir('login.php');
        }

        $ultimo = (int) ($_SESSION['ultimo_acceso'] ?? time());
        if (time() - $ultimo > 7200) {
            self::cerrar();
            session_start();
            flash_set('error', 'La sesión expiró. Ingrese de nuevo.');
            redirigir('login.php');
        }

        $registro = Usuario::porId((int) $_SESSION['usuario_id']);
        if (!$registro || $registro['estado'] !== 'activo') {
            self::cerrar();
            session_start();
            flash_set('error', 'Su usuario no está activo.');
            redirigir('login.php');
        }

        $_SESSION['ultimo_acceso'] = time();
        $_SESSION['nombre'] = $registro['nombre'];
        $_SESSION['apellido'] = $registro['apellido'];
        $_SESSION['usuario'] = $registro['usuario'];
        $_SESSION['rol'] = $registro['rol'];
    }

    public static function exigirAdmin(): void
    {
        self::exigirLogin();
        if (!self::esAdmin()) {
            flash_set('error', 'No tiene permiso para esta acción.');
            redirigir('index.php?m=dashboard');
        }
    }

    private static function hashInvalido(): string
    {
        static $hash = null;
        if ($hash === null) {
            $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        }

        return $hash;
    }
}
