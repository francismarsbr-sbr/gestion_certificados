<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $primerAdministrador ? 'Registrar administrador' : 'Registro' ?> · <?= e(APP_NOMBRE) ?></title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="cuerpo-login">
<main class="login-caja login-amplia">
    <?php if (is_file(RAIZ . '/assets/img/logo.png')): ?>
        <img class="logo-login" src="assets/img/logo.png" alt="Logotipo institucional">
    <?php endif; ?>
    <?php if ($primerAdministrador): ?>
        <h1>Registrar administrador</h1>
        <p class="ayuda">No hay un administrador activo. Cree esa cuenta para empezar. Después podrá ingresar y registrar a los demás usuarios.</p>
    <?php else: ?>
        <h1>Registrar usuario</h1>
        <p class="ayuda">La cuenta nueva entra como operador. Un administrador puede cambiar el rol después.</p>
    <?php endif; ?>
    <?php flash_html(); ?>
    <form method="post" action="registro.php" class="formulario rejilla">
        <?= csrf_campo() ?>
        <label class="campo">
            <span>Nombre</span>
            <input name="nombre" required maxlength="120" autocomplete="given-name" value="<?= e(old('nombre')) ?>">
        </label>
        <label class="campo">
            <span>Apellido</span>
            <input name="apellido" required maxlength="120" autocomplete="family-name" value="<?= e(old('apellido')) ?>">
        </label>
        <label class="campo">
            <span>Usuario</span>
            <input name="usuario" required minlength="4" maxlength="50" spellcheck="false" autocomplete="username" value="<?= e(old('usuario')) ?>">
            <small class="ayuda">De 4 a 50 caracteres: letras, números, punto o guion.</small>
        </label>
        <label class="campo">
            <span>Correo</span>
            <input type="email" name="correo" required maxlength="150" autocomplete="email" value="<?= e(old('correo')) ?>">
        </label>
        <label class="campo">
            <span>Clave</span>
            <input type="password" name="clave" required minlength="8" maxlength="72" autocomplete="new-password">
            <small class="ayuda">Entre 8 y 72 caracteres.</small>
        </label>
        <label class="campo">
            <span>Confirmar clave</span>
            <input type="password" name="clave_confirmacion" required minlength="8" maxlength="72" autocomplete="new-password">
        </label>
        <div class="ancho">
            <button type="submit" class="btn btn-pri btn-bloque"><?= $primerAdministrador ? 'Crear administrador' : 'Crear cuenta' ?></button>
        </div>
    </form>
    <?php if (!$primerAdministrador): ?>
        <p class="pie-login"><a href="login.php">Ya tengo cuenta</a></p>
    <?php endif; ?>
</main>
</body>
</html>
<?php old_limpiar(); ?>
