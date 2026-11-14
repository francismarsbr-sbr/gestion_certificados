<?php $editando = old('id') !== ''; ?>
<section class="encabezado-pagina">
    <div>
        <h1>Usuarios</h1>
        <p>El administrador crea cuentas de operador o de otro administrador, y puede desactivarlas.</p>
    </div>
    <button type="button" class="btn btn-pri" data-abrir="modal-usuario" data-titulo="Nuevo usuario" data-limpiar="1">Nuevo usuario</button>
</section>
<form class="filtros" method="get" action="index.php">
    <input type="hidden" name="m" value="usuarios">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nombre, usuario o correo" maxlength="120">
    <button type="submit" class="btn btn-sec">Buscar</button>
    <?php if ($q !== ''): ?><a class="btn btn-texto" href="index.php?m=usuarios">Limpiar</a><?php endif; ?>
</form>
<div class="tabla-contenedor">
    <table>
        <thead><tr><th>Nombre</th><th>Usuario</th><th class="col-opcional">Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
            <?php if ($listado['filas'] === []): ?>
                <tr><td colspan="6" class="vacio">No hay usuarios para mostrar.</td></tr>
            <?php endif; ?>
            <?php foreach ($listado['filas'] as $fila): ?>
                <?php
                $payload = [
                    'id' => (int) $fila['id'],
                    'nombre' => $fila['nombre'],
                    'apellido' => $fila['apellido'],
                    'usuario' => $fila['usuario'],
                    'correo' => $fila['correo'],
                    'rol' => $fila['rol'],
                    'estado' => $fila['estado'],
                ];
                $esYo = (int) $fila['id'] === (int) Auth::actual()['id'];
                ?>
                <tr>
                    <td><?= e($fila['nombre'] . ' ' . $fila['apellido']) ?></td>
                    <td class="codigo"><?= e($fila['usuario']) ?></td>
                    <td class="col-opcional"><?= e($fila['correo']) ?></td>
                    <td><?= $fila['rol'] === 'administrador' ? 'Administrador' : 'Operador' ?></td>
                    <td><span class="badge badge-<?= $fila['estado'] === 'activo' ? 'ok' : 'neutro' ?>"><?= $fila['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?></span></td>
                    <td class="acciones">
                        <button type="button" class="btn btn-sec" data-abrir="modal-usuario" data-titulo="Editar usuario" data-editar="<?= json_html($payload) ?>">Editar</button>
                        <?php if (!$esYo): ?>
                            <form method="post" action="index.php?m=usuarios&amp;a=estado" data-confirmar="¿Cambiar el estado de este usuario?">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $fila['id'] ?>">
                                <input type="hidden" name="estado" value="<?= $fila['estado'] === 'activo' ? 'inactivo' : 'activo' ?>">
                                <button type="submit" class="btn btn-texto"><?= $fila['estado'] === 'activo' ? 'Desactivar' : 'Activar' ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php html_paginacion((int) $listado['pagina'], (int) $listado['paginas'], (int) $listado['total']); ?>

<div class="modal" id="modal-usuario" hidden>
    <div class="modal-caja" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-usuario">
        <div class="modal-barra">
            <h2 class="modal-titulo" id="titulo-modal-usuario"><?= $editando ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
            <button type="button" class="btn btn-texto" data-cerrar>Cerrar</button>
        </div>
        <form method="post" action="index.php?m=usuarios&amp;a=guardar" class="formulario rejilla">
            <?= csrf_campo() ?>
            <input type="hidden" name="id" value="<?= e(old('id')) ?>">
            <label class="campo"><span>Nombre</span><input name="nombre" required maxlength="80" value="<?= e(old('nombre')) ?>"></label>
            <label class="campo"><span>Apellido</span><input name="apellido" required maxlength="80" value="<?= e(old('apellido')) ?>"></label>
            <label class="campo"><span>Usuario</span><input name="usuario" required minlength="4" maxlength="50" spellcheck="false" value="<?= e(old('usuario')) ?>"></label>
            <label class="campo"><span>Correo</span><input type="email" name="correo" required maxlength="150" value="<?= e(old('correo')) ?>"></label>
            <label class="campo"><span>Rol</span>
                <select name="rol">
                    <option value="operador"<?= old('rol', 'operador') === 'operador' ? ' selected' : '' ?>>Operador</option>
                    <option value="administrador"<?= old('rol') === 'administrador' ? ' selected' : '' ?>>Administrador</option>
                </select>
            </label>
            <label class="campo"><span>Estado</span>
                <select name="estado">
                    <option value="activo"<?= old('estado', 'activo') === 'activo' ? ' selected' : '' ?>>Activo</option>
                    <option value="inactivo"<?= old('estado') === 'inactivo' ? ' selected' : '' ?>>Inactivo</option>
                </select>
            </label>
            <label class="campo"><span>Clave</span><input type="password" name="clave" minlength="8" maxlength="72" autocomplete="new-password" <?= $editando ? '' : 'required' ?>><small class="ayuda">Al editar, déjela en blanco si no va a cambiarla.</small></label>
            <label class="campo"><span>Confirmar clave</span><input type="password" name="clave_confirmacion" minlength="8" maxlength="72" autocomplete="new-password" <?= $editando ? '' : 'required' ?>></label>
            <div class="ancho acciones"><button type="submit" class="btn btn-pri">Guardar</button></div>
        </form>
    </div>
</div>
