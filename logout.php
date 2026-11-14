<?php
declare(strict_types=1);
require __DIR__ . '/libs/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        csrf_verificar();
    } catch (Throwable $e) {
        flash_set('error', 'No se pudo cerrar la sesión. Intente de nuevo.');
        redirigir(Auth::haySesion() ? 'index.php' : 'login.php');
    }
    Auth::cerrar();
    redirigir('login.php');
}

redirigir(Auth::haySesion() ? 'index.php' : 'login.php');
