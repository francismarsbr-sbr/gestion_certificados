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

$primerAdministrador = !Usuario::hayAdministradorActivo();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        csrf_verificar();
        $cuenta = Usuario::registrarCuenta($_POST);
        Auth::establecer($cuenta);
        flash_set('ok', $cuenta['rol'] === 'administrador'
            ? 'Cuenta de administrador creada.'
            : 'Cuenta creada. Ya puede usar el sistema.');
        redirigir('index.php');
    } catch (AppException $e) {
        guardar_reintento(null, $_POST);
        flash_set('error', $e->getMessage());
        redirigir('registro.php');
    } catch (Throwable $e) {
        registrar_error($e);
        flash_set('error', 'No se pudo crear la cuenta.');
        redirigir('registro.php');
    }
}

require RAIZ . '/views/login/registro.php';
