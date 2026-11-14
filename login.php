<?php
declare(strict_types=1);
require __DIR__ . '/libs/bootstrap.php';

if (Auth::haySesion()) {
    $activo = Usuario::porId((int) $_SESSION['usuario_id']);
    if ($activo && $activo['estado'] === 'activo') {
        redirigir('index.php');
    }
    Auth::cerrar();
    session_start();
}

if (!Usuario::hayAdministradorActivo()) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        flash_set('error', 'Primero registre un administrador.');
    }
    redirigir('registro.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        csrf_verificar();
        Auth::iniciar((string) ($_POST['usuario'] ?? ''), (string) ($_POST['clave'] ?? ''));
        redirigir('index.php');
    } catch (AppException $e) {
        $_SESSION['old'] = ['usuario' => trim((string) ($_POST['usuario'] ?? ''))];
        flash_set('error', $e->getMessage());
        redirigir('login.php');
    } catch (Throwable $e) {
        registrar_error($e);
        flash_set('error', 'No se pudo iniciar sesión.');
        redirigir('login.php');
    }
}

require RAIZ . '/views/login/index.php';
