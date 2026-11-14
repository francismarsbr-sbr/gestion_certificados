<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar · <?= e(APP_NOMBRE) ?></title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="cuerpo-login">
<main class="login-caja">
    <?php if (is_file(RAIZ . '/assets/img/logo.png')): ?>
        <img class="logo-login" src="assets/img/logo.png" alt="Logotipo institucional">
    <?php endif; ?>
    <h1>Gestión de certificados</h1>
    <p class="ayuda">Ingrese con su usuario para administrar cursos, estudiantes y certificados.</p>
    <?php flash_html(); ?>
    <form method="post" action="login.php" class="formulario">
        <?= csrf_campo() ?>
        <label class="campo">
            <span>Usuario</span>
            <input name="usuario" autocomplete="username" required maxlength="50" spellcheck="false" value="<?= e(old('usuario')) ?>">
        </label>
        <label class="campo">
            <span>Clave</span>
            <input name="clave" type="password" autocomplete="current-password" required maxlength="72">
        </label>
        <button type="submit" class="btn btn-pri btn-bloque">Ingresar</button>
    </form>
    <p class="pie-login"><a href="registro.php">Registrar un nuevo usuario</a></p>
</main>
</body>
</html>
<?php old_limpiar(); ?>
