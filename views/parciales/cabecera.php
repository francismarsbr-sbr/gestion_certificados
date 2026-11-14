<?php
declare(strict_types=1);
$titulo = $titulo ?? 'Panel';
$abrirModal = '';
if (!empty($_SESSION['abrir_modal']) && is_string($_SESSION['abrir_modal'])) {
    $abrirModal = $_SESSION['abrir_modal'];
    unset($_SESSION['abrir_modal']);
}
$usuarioActual = Auth::actual();
$modActual = (string) ($_GET['m'] ?? 'dashboard');
$rolVisible = $usuarioActual['rol'] === 'administrador' ? 'Administrador' : 'Operador';
$menu = [
    'dashboard' => 'Dashboard',
    'cursos' => 'Cursos',
    'estudiantes' => 'Estudiantes',
    'inscripciones' => 'Inscripciones',
    'certificados' => 'Certificados',
];
if (Auth::esAdmin()) {
    $menu['usuarios'] = 'Usuarios';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · <?= e(APP_NOMBRE) ?></title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body<?= $abrirModal !== '' ? ' data-abrir-modal="' . e($abrirModal) . '"' : '' ?>>
<a class="saltar" href="#contenido">Saltar al contenido</a>
<div class="fondo-menu" id="fondo-menu"></div>
<aside class="lateral">
    <div class="marca">
        <?php if (is_file(RAIZ . '/assets/img/logo.png')): ?>
            <img src="assets/img/logo.png" alt="">
        <?php endif; ?>
        <div>
            <strong>Certificados</strong>
            <span>Panel administrativo</span>
        </div>
    </div>
    <nav>
        <?php foreach ($menu as $clave => $etiqueta): ?>
            <a href="index.php?m=<?= e($clave) ?>"<?= $modActual === $clave ? ' aria-current="page"' : '' ?>><?= e($etiqueta) ?></a>
        <?php endforeach; ?>
    </nav>
</aside>
<div class="columna">
    <header class="barra">
        <button type="button" class="btn btn-sec menu-movil" id="abrir-menu">Menú</button>
        <div class="barra-usuario">
            <div>
                <strong><?= e($usuarioActual['nombre'] . ' ' . $usuarioActual['apellido']) ?></strong>
                <span><?= e($rolVisible) ?></span>
            </div>
            <a class="btn btn-texto" href="index.php?m=cuenta">Mi cuenta</a>
            <form method="post" action="logout.php">
                <?= csrf_campo() ?>
                <button type="submit" class="btn btn-sec">Cerrar sesión</button>
            </form>
        </div>
    </header>
    <main class="contenido" id="contenido">
        <?php flash_html(); ?>
