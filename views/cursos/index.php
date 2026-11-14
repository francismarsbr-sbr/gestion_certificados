<?php
$modulosFormulario = [];
$cantidadModulos = '';
if (isset($_SESSION['old']['cantidad_modulos'])) {
    $cantidadModulos = (string) max(0, min(12, (int) $_SESSION['old']['cantidad_modulos']));
    $titulosViejos = $_SESSION['old']['modulos_titulo'] ?? [];
    $contenidosViejos = $_SESSION['old']['modulos_contenido'] ?? [];
    for ($i = 0; $i < (int) $cantidadModulos; $i++) {
        $modulosFormulario[] = [
            'titulo' => is_array($titulosViejos) ? (string) ($titulosViejos[$i] ?? '') : '',
            'contenido' => is_array($contenidosViejos) ? (string) ($contenidosViejos[$i] ?? '') : '',
        ];
    }
}
?>
<section class="encabezado-pagina">
    <div>
        <h1>Cursos</h1>
        <p>El código se genera con las iniciales y un consecutivo, y no cambia al editar el nombre.</p>
    </div>
    <?php if (Auth::esAdmin()): ?>
        <button type="button" class="btn btn-pri" data-abrir="modal-curso" data-titulo="Registrar curso" data-limpiar="1">Nuevo curso</button>
    <?php endif; ?>
</section>
<form class="filtros" method="get" action="index.php">
    <input type="hidden" name="m" value="cursos">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nombre, código, iniciales o facilitador" maxlength="120">
    <select name="estado">
        <option value="">Todos los estados</option>
        <option value="activo"<?= $estado === 'activo' ? ' selected' : '' ?>>Activos</option>
        <option value="inactivo"<?= $estado === 'inactivo' ? ' selected' : '' ?>>Inactivos</option>
    </select>
    <button type="submit" class="btn btn-sec">Buscar</button>
    <?php if ($q !== '' || $estado !== ''): ?><a class="btn btn-texto" href="index.php?m=cursos">Limpiar</a><?php endif; ?>
</form>
<div class="tabla-contenedor">
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Curso</th>
                <th class="col-opcional">Horas</th>
                <th class="col-opcional">Realización</th>
                <th class="col-opcional">Facilitador</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($listado['filas'] === []): ?>
                <tr><td colspan="7" class="vacio">No hay cursos para mostrar.</td></tr>
            <?php endif; ?>
            <?php foreach ($listado['filas'] as $fila): ?>
                <?php
                $payload = [
                    'id' => (int) $fila['id'],
                    'nombre' => $fila['nombre'],
                    'iniciales' => $fila['iniciales'],
                    'codigo_curso' => $fila['codigo_curso'],
                    'descripcion' => $fila['descripcion'],
                    'objetivo' => $fila['objetivo'],
                    'modulos' => MallaParser::paraFormulario($fila['contenido_programatico'] ?? ''),
                    'fecha_inicio' => $fila['fecha_inicio'],
                    'fecha_fin' => $fila['fecha_fin'],
                    'duracion_horas' => $fila['duracion_horas'],
                    'facilitador' => $fila['facilitador'],
                    'institucion' => $fila['institucion'],
                ];
                ?>
                <tr>
                    <td class="codigo"><?= e($fila['codigo_curso']) ?></td>
                    <td><?= e($fila['nombre']) ?><br><small><?= e($fila['iniciales']) ?></small></td>
                    <td class="col-opcional"><?= $fila['duracion_horas'] ? e((string) $fila['duracion_horas']) : '—' ?></td>
                    <td class="col-opcional"><?= e(fecha_es($fila['fecha_inicio'])) ?> – <?= e(fecha_es($fila['fecha_fin'])) ?></td>
                    <td class="col-opcional"><?= e($fila['facilitador'] ?: '—') ?></td>
                    <td><span class="badge badge-<?= $fila['estado'] === 'activo' ? 'ok' : 'neutro' ?>"><?= $fila['estado'] === 'activo' ? 'Activo' : 'Inactivo' ?></span></td>
                    <td class="acciones">
                        <?php if (Auth::esAdmin()): ?>
                            <button type="button" class="btn btn-sec" data-abrir="modal-curso" data-titulo="Editar curso" data-editar="<?= json_html($payload) ?>">Editar</button>
                            <form method="post" action="index.php?m=cursos&amp;a=estado" data-confirmar="¿Cambiar el estado de este curso?">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $fila['id'] ?>">
                                <input type="hidden" name="estado" value="<?= $fila['estado'] === 'activo' ? 'inactivo' : 'activo' ?>">
                                <button type="submit" class="btn btn-texto"><?= $fila['estado'] === 'activo' ? 'Desactivar' : 'Activar' ?></button>
                            </form>
                        <?php else: ?>
                            <button type="button" class="btn btn-sec" data-abrir="modal-curso" data-titulo="Consultar curso" data-ver="1" data-editar="<?= json_html($payload) ?>">Ver</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php html_paginacion((int) $listado['pagina'], (int) $listado['paginas'], (int) $listado['total']); ?>

<div class="modal" id="modal-curso" hidden>
    <div class="modal-caja" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-curso">
        <div class="modal-barra">
            <h2 class="modal-titulo" id="titulo-modal-curso"><?= old('id') !== '' ? 'Editar curso' : 'Registrar curso' ?></h2>
            <button type="button" class="btn btn-texto" data-cerrar>Cerrar</button>
        </div>
        <form method="post" action="index.php?m=cursos&amp;a=guardar" class="formulario rejilla" data-modulos-curso>
            <?= csrf_campo() ?>
            <input type="hidden" name="id" value="<?= e(old('id')) ?>">
            <label class="campo ancho"><span>Nombre</span><input name="nombre" required maxlength="200" value="<?= e(old('nombre')) ?>"></label>
            <label class="campo"><span>Iniciales</span><input name="iniciales" required minlength="2" maxlength="10" pattern="[A-Za-z0-9]{2,10}" value="<?= e(old('iniciales')) ?>" <?= old('id') !== '' ? 'readonly' : '' ?>><small class="ayuda">Ejemplo: IAP genera IAP-001. Luego el código no cambia.</small></label>
            <label class="campo"><span>Código</span><input name="codigo_curso" readonly value="<?= e(old('codigo_curso')) ?>" placeholder="Se asigna al guardar"></label>
            <label class="campo"><span>Inicio</span><input type="date" name="fecha_inicio" required value="<?= e(old('fecha_inicio')) ?>"></label>
            <label class="campo"><span>Culminación</span><input type="date" name="fecha_fin" required value="<?= e(old('fecha_fin')) ?>"></label>
            <label class="campo"><span>Horas académicas</span><input type="number" name="duracion_horas" required min="1" max="10000" value="<?= e(old('duracion_horas')) ?>"></label>
            <label class="campo"><span>Facilitador</span><input name="facilitador" required maxlength="150" value="<?= e(old('facilitador')) ?>"></label>
            <label class="campo ancho"><span>Institución emisora</span><input name="institucion" required maxlength="200" value="<?= e(old('institucion')) ?>"></label>
            <label class="campo ancho"><span>Descripción</span><textarea name="descripcion" maxlength="5000" rows="3"><?= e(old('descripcion')) ?></textarea></label>
            <label class="campo ancho"><span>Objetivo general</span><textarea name="objetivo" maxlength="5000" rows="3"><?= e(old('objetivo')) ?></textarea></label>
            <div class="ancho">
                <div class="modulos-cantidad">
                    <label class="campo">
                        <span>¿Cuántos módulos tiene el curso?</span>
                        <input type="number" name="cantidad_modulos" min="0" max="12" required value="<?= e($cantidadModulos) ?>">
                    </label>
                    <button type="button" class="btn btn-sec" data-generar-modulos>Generar espacios</button>
                </div>
                <small class="ayuda">Se abre un espacio por módulo. El título y cada línea del contenido se imprimen ordenados en el reverso del certificado.</small>
                <div class="lista-modulos" data-lista-modulos>
                    <?php foreach ($modulosFormulario as $indice => $modulo): ?>
                        <fieldset class="modulo-editor" data-modulo="1">
                            <legend>Módulo <?= $indice + 1 ?></legend>
                            <label class="campo"><span>Título</span><input name="modulos_titulo[]" required maxlength="180" value="<?= e($modulo['titulo']) ?>"></label>
                            <label class="campo"><span>Contenido</span><textarea name="modulos_contenido[]" maxlength="3000" rows="4"><?= e($modulo['contenido']) ?></textarea></label>
                        </fieldset>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if (Auth::esAdmin()): ?>
                <div class="ancho acciones"><button type="submit" class="btn btn-pri">Guardar</button></div>
            <?php endif; ?>
        </form>
    </div>
</div>
