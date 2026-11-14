<section class="encabezado-pagina">
    <div>
        <h1>Estudiantes</h1>
        <p>El documento de identidad no puede repetirse. La inscripción en un curso se hace aparte.</p>
    </div>
    <button type="button" class="btn btn-pri" data-abrir="modal-estudiante" data-titulo="Registrar estudiante" data-limpiar="1">Nuevo estudiante</button>
</section>
<form class="filtros" method="get" action="index.php">
    <input type="hidden" name="m" value="estudiantes">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nombre, apellido o documento" maxlength="120">
    <button type="submit" class="btn btn-sec">Buscar</button>
    <?php if ($q !== ''): ?><a class="btn btn-texto" href="index.php?m=estudiantes">Limpiar</a><?php endif; ?>
</form>
<div class="tabla-contenedor">
    <table>
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Documento</th>
                <th class="col-opcional">Correo</th>
                <th class="col-opcional">Teléfono</th>
                <th class="col-opcional">Institución</th>
                <th class="col-opcional">Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($listado['filas'] === []): ?>
                <tr><td colspan="7" class="vacio">No hay estudiantes para mostrar.</td></tr>
            <?php endif; ?>
            <?php foreach ($listado['filas'] as $fila): ?>
                <?php
                $payload = [
                    'id' => (int) $fila['id'],
                    'nombres' => $fila['nombres'],
                    'apellidos' => $fila['apellidos'],
                    'documento' => $fila['documento'],
                    'correo' => $fila['correo'],
                    'telefono' => $fila['telefono'],
                    'institucion' => $fila['institucion'],
                ];
                ?>
                <tr>
                    <td><?= e($fila['apellidos'] . ', ' . $fila['nombres']) ?></td>
                    <td class="codigo"><?= e($fila['documento']) ?></td>
                    <td class="col-opcional"><?= e($fila['correo'] ?: '—') ?></td>
                    <td class="col-opcional"><?= e($fila['telefono'] ?: '—') ?></td>
                    <td class="col-opcional"><?= e($fila['institucion'] ?: '—') ?></td>
                    <td class="col-opcional"><?= e(fecha_es((string) $fila['fecha_registro'])) ?></td>
                    <td><button type="button" class="btn btn-sec" data-abrir="modal-estudiante" data-titulo="Editar estudiante" data-editar="<?= json_html($payload) ?>">Editar</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php html_paginacion((int) $listado['pagina'], (int) $listado['paginas'], (int) $listado['total']); ?>

<div class="modal" id="modal-estudiante" hidden>
    <div class="modal-caja" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-estudiante">
        <div class="modal-barra">
            <h2 class="modal-titulo" id="titulo-modal-estudiante"><?= old('id') !== '' ? 'Editar estudiante' : 'Registrar estudiante' ?></h2>
            <button type="button" class="btn btn-texto" data-cerrar>Cerrar</button>
        </div>
        <form method="post" action="index.php?m=estudiantes&amp;a=guardar" class="formulario rejilla">
            <?= csrf_campo() ?>
            <input type="hidden" name="id" value="<?= e(old('id')) ?>">
            <label class="campo"><span>Nombres</span><input name="nombres" required maxlength="120" value="<?= e(old('nombres')) ?>"></label>
            <label class="campo"><span>Apellidos</span><input name="apellidos" required maxlength="120" value="<?= e(old('apellidos')) ?>"></label>
            <label class="campo"><span>Documento</span><input name="documento" required maxlength="30" spellcheck="false" value="<?= e(old('documento')) ?>"></label>
            <label class="campo"><span>Correo</span><input type="email" name="correo" maxlength="150" value="<?= e(old('correo')) ?>"></label>
            <label class="campo"><span>Teléfono</span><input name="telefono" maxlength="30" value="<?= e(old('telefono')) ?>"></label>
            <label class="campo"><span>Institución u organización</span><input name="institucion" maxlength="200" value="<?= e(old('institucion')) ?>"></label>
            <div class="ancho acciones"><button type="submit" class="btn btn-pri">Guardar</button></div>
        </form>
    </div>
</div>
