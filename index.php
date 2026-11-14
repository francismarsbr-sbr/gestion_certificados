<?php
declare(strict_types=1);
require __DIR__ . '/libs/bootstrap.php';

Auth::exigirLogin();

$modulo = (string) ($_GET['m'] ?? 'dashboard');
$accion = (string) ($_GET['a'] ?? 'indice');
$mapa = [
    'dashboard' => [DashboardController::class, false],
    'cursos' => [CursoController::class, false],
    'estudiantes' => [EstudianteController::class, false],
    'inscripciones' => [InscripcionController::class, false],
    'certificados' => [CertificadoController::class, false],
    'usuarios' => [UsuarioController::class, true],
    'cuenta' => [CuentaController::class, false],
];

if (!isset($mapa[$modulo])) {
    $modulo = 'dashboard';
}

[$clase, $soloAdmin] = $mapa[$modulo];
if ($soloAdmin) {
    Auth::exigirAdmin();
}

(new $clase())->manejar($accion);
