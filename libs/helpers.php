<?php

declare(strict_types=1);

function db(): mysqli
{
    return Conexion::obtener();
}

function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function redirigir(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function url_mod(string $modulo, array $parametros = []): string
{
    return 'index.php?' . http_build_query(array_merge(['m' => $modulo], $parametros));
}

function url_actual(array $cambios = []): string
{
    $parametros = array_merge($_GET, $cambios);
    foreach ($parametros as $clave => $valor) {
        if ($valor === '' || $valor === null) {
            unset($parametros[$clave]);
        }
    }
    $script = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    $consulta = http_build_query($parametros);

    return $script . ($consulta !== '' ? '?' . $consulta : '');
}

function url_publica(string $ruta = ''): string
{
    $base = defined('APP_URL') ? rtrim((string) APP_URL, '/') : '';
    if ($base === '' && !empty($_SERVER['HTTP_HOST'])) {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $directorio = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($directorio === '/' || $directorio === '.') {
            $directorio = '';
        }
        $base = ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $directorio;
    }
    if ($base === '') {
        $base = 'http://localhost/gestion_certificados';
    }

    return $ruta === '' ? $base : $base . '/' . ltrim($ruta, '/');
}

function registrar_error(Throwable $e): void
{
    error_log('[gestion_certificados] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
}

function cabeceras_seguridad(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store, no-cache, must-revalidate');
}

function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $ruta = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($ruta === '' || $ruta === '.' || $ruta === '/') {
        $ruta = '/';
    }
    session_name('GESTCERT');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $ruta,
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verificar(): void
{
    $enviado = (string) ($_POST['csrf'] ?? '');
    $guardado = (string) ($_SESSION['csrf'] ?? '');
    if ($guardado === '' || !hash_equals($guardado, $enviado)) {
        throw new AppException('El formulario expiró. Vuelva a enviarlo.');
    }
}

function flash_set(string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flash_html(): void
{
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $tipo = ($flash['tipo'] ?? '') === 'ok' ? 'ok' : 'error';
    echo '<div class="alerta alerta-' . $tipo . '" role="status">' . e((string) ($flash['mensaje'] ?? '')) . '</div>';
}

function guardar_reintento(?string $modal, array $datos): void
{
    foreach (['csrf', 'password', 'clave', 'clave_actual', 'clave_nueva', 'clave_confirmacion'] as $sensible) {
        unset($datos[$sensible]);
    }
    $_SESSION['old'] = $datos;
    if ($modal) {
        $_SESSION['abrir_modal'] = $modal;
    }
}

function old(string $clave, mixed $defecto = ''): string
{
    if (!isset($_SESSION['old']) || !is_array($_SESSION['old']) || !array_key_exists($clave, $_SESSION['old'])) {
        return (string) $defecto;
    }

    return (string) $_SESSION['old'][$clave];
}

function old_limpiar(): void
{
    unset($_SESSION['old']);
}

function pagina_solicitada(string $parametro = 'pagina'): int
{
    return max(1, (int) ($_GET[$parametro] ?? 1));
}

function comodin(string $texto): string
{
    return '%' . addcslashes(trim($texto), '%_\\') . '%';
}

function fecha_es(?string $fecha): string
{
    if ($fecha === null || $fecha === '') {
        return '—';
    }
    $marca = strtotime($fecha);

    return $marca === false ? '—' : date('d/m/Y', $marca);
}

function fecha_larga(?string $fecha): string
{
    if ($fecha === null || $fecha === '') {
        return '';
    }
    $marca = strtotime($fecha);
    if ($marca === false) {
        return '';
    }
    $meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    return (int) date('j', $marca) . ' de ' . $meses[(int) date('n', $marca)] . ' de ' . date('Y', $marca);
}

function json_html(array $datos): string
{
    $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    return e($json === false ? '{}' : $json);
}

function html_paginacion(int $pagina, int $totalPaginas, int $total, string $parametro = 'pagina'): void
{
    echo '<div class="paginacion">';
    echo '<span>' . (int) $total . ' registro' . ($total === 1 ? '' : 's') . '</span>';
    if ($totalPaginas > 1) {
        echo '<div class="paginacion-controles">';
        if ($pagina > 1) {
            echo '<a class="btn btn-sec" href="' . e(url_actual([$parametro => $pagina - 1])) . '">Anterior</a>';
        }
        echo '<span>Página ' . (int) $pagina . ' de ' . (int) $totalPaginas . '</span>';
        if ($pagina < $totalPaginas) {
            echo '<a class="btn btn-sec" href="' . e(url_actual([$parametro => $pagina + 1])) . '">Siguiente</a>';
        }
        echo '</div>';
    }
    echo '</div>';
}

function enlazar(mysqli_stmt $stmt, string $tipos, array $params): void
{
    if ($tipos === '') {
        return;
    }
    if (strlen($tipos) !== count($params)) {
        throw new RuntimeException('Los parámetros no coinciden con la consulta.');
    }
    $referencias = [];
    foreach ($params as $indice => $valor) {
        $referencias[$indice] = $valor;
    }
    $lista = [];
    foreach ($referencias as $indice => &$valor) {
        $lista[$indice] = &$valor;
    }
    unset($valor);
    $stmt->bind_param($tipos, ...$lista);
}

function filas(string $sql, string $tipos = '', array $params = []): array
{
    $stmt = db()->prepare($sql);
    enlazar($stmt, $tipos, $params);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $datos = $resultado->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $datos;
}

function fila(string $sql, string $tipos = '', array $params = []): ?array
{
    $datos = filas($sql, $tipos, $params);

    return $datos[0] ?? null;
}

function escalar(string $sql, string $tipos = '', array $params = []): mixed
{
    $stmt = db()->prepare($sql);
    enlazar($stmt, $tipos, $params);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $registro = $resultado->fetch_row();
    $stmt->close();

    return $registro[0] ?? null;
}

function ejecutar(string $sql, string $tipos = '', array $params = []): int
{
    $stmt = db()->prepare($sql);
    enlazar($stmt, $tipos, $params);
    $stmt->execute();
    $afectadas = $stmt->affected_rows;
    $stmt->close();

    return $afectadas;
}

function insertar(string $sql, string $tipos, array $params): int
{
    ejecutar($sql, $tipos, $params);

    return (int) db()->insert_id;
}

function traducir_duplicado(mysqli_sql_exception $e, array $mensajes): void
{
    if ((int) $e->getCode() !== 1062) {
        throw $e;
    }
    $texto = $e->getMessage();
    foreach ($mensajes as $fragmento => $mensaje) {
        if (str_contains($texto, $fragmento)) {
            throw new AppException($mensaje);
        }
    }
    throw new AppException('El registro entra en conflicto con uno existente.');
}
