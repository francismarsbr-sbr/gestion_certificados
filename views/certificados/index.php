<section class="encabezado-pagina">
    <div>
        <h1>Certificados</h1>
        <p>Elija el curso y genere el certificado de cada inscrito. El código y el QR se crean solos.</p>
    </div>
</section>
<form class="filtros" method="get" action="index.php">
    <input type="hidden" name="m" value="certificados">
    <label class="campo campo-linea">
        <span>Curso</span>
        <select name="curso" data-enviar>
            <option value="">Seleccione un curso</option>
            <?php foreach ($cursos as $opcion): ?>
                <option value="<?= (int) $opcion['id'] ?>"<?= $curso && (int) $curso['id'] === (int) $opcion['id'] ? ' selected' : '' ?>><?= e($opcion['codigo_curso'] . ' · ' . $opcion['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
</form>
<?php if ($curso): ?>
    <?php $faltan = Curso::faltantes($curso); ?>
    <?php if ($faltan !== []): ?>
        <p class="aviso">Este curso todavía no puede emitir certificados. Falta: <?= e(implode(', ', $faltan)) ?>. <?php if (Auth::esAdmin()): ?><a href="index.php?m=cursos">Completar el curso</a><?php endif; ?></p>
    <?php endif; ?>
    <div class="resumen-linea">
        <span><strong><?= (int) $resumen['total'] ?></strong> inscritos</span>
        <span><strong><?= (int) $resumen['con_certificado'] ?></strong> con certificado vigente</span>
        <span><strong><?= (int) $resumen['sin_certificado'] ?></strong> pendientes</span>
    </div>
    <form class="filtros" method="get" action="index.php">
        <input type="hidden" name="m" value="certificados">
        <input type="hidden" name="curso" value="<?= (int) $curso['id'] ?>">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Filtrar inscritos" maxlength="120">
        <button type="submit" class="btn btn-sec">Filtrar</button>
    </form>
    <div class="tabla-contenedor">
        <table>
            <thead><tr><th>Estudiante</th><th>Documento</th><th>Inscripción</th><th>Certificado</th><th>Acción</th></tr></thead>
            <tbody>
                <?php if ($listado['filas'] === []): ?>
                    <tr><td colspan="5" class="vacio">No hay inscritos para mostrar.</td></tr>
                <?php endif; ?>
                <?php foreach ($listado['filas'] as $fila): ?>
                    <tr>
                        <td><?= e($fila['apellidos'] . ', ' . $fila['nombres']) ?></td>
                        <td class="codigo"><?= e($fila['documento']) ?></td>
                        <td><?= e(fecha_es((string) $fila['fecha_inscripcion'])) ?></td>
                        <td>
                            <?php if ($fila['certificado_id']): ?>
                                <span class="badge badge-ok">Vigente</span>
                                <span class="codigo"><?= e($fila['codigo_certificado']) ?></span>
                            <?php else: ?>
                                <span class="badge badge-aviso">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td class="acciones">
                            <?php if ($fila['certificado_id']): ?>
                                <a class="btn btn-sec" href="descargar.php?id=<?= (int) $fila['certificado_id'] ?>">Descargar</a>
                            <?php else: ?>
                                <form method="post" action="index.php?m=certificados&amp;a=generar">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id_inscripcion" value="<?= (int) $fila['id'] ?>">
                                    <input type="hidden" name="id_curso" value="<?= (int) $curso['id'] ?>">
                                    <button type="submit" class="btn btn-pri">Generar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php html_paginacion((int) $listado['pagina'], (int) $listado['paginas'], (int) $listado['total']); ?>
<?php endif; ?>

<section class="panel">
    <h2>Historial de certificados</h2>
    <form class="filtros" method="get" action="index.php">
        <input type="hidden" name="m" value="certificados">
        <?php if ($curso): ?><input type="hidden" name="curso" value="<?= (int) $curso['id'] ?>"><?php endif; ?>
        <input type="search" name="hq" value="<?= e($hq) ?>" placeholder="Código, estudiante o curso" maxlength="120">
        <select name="estado">
            <option value="">Todos</option>
            <option value="activo"<?= $estado === 'activo' ? ' selected' : '' ?>>Vigentes</option>
            <option value="anulado"<?= $estado === 'anulado' ? ' selected' : '' ?>>Anulados</option>
        </select>
        <select name="curso_h">
            <option value="0">Todos los cursos</option>
            <?php foreach ($cursos as $opcion): ?>
                <option value="<?= (int) $opcion['id'] ?>"<?= $cursoHistorial === (int) $opcion['id'] ? ' selected' : '' ?>><?= e($opcion['codigo_curso']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sec">Consultar</button>
    </form>
    <div class="tabla-contenedor">
        <table>
            <thead><tr><th>Código</th><th>Estudiante</th><th>Curso</th><th>Emisión</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php if ($historial['filas'] === []): ?>
                    <tr><td colspan="6" class="vacio">No hay certificados emitidos con ese filtro.</td></tr>
                <?php endif; ?>
                <?php foreach ($historial['filas'] as $fila): ?>
                    <tr>
                        <td class="codigo"><?= e($fila['codigo_certificado']) ?></td>
                        <td><?= e($fila['nombres'] . ' ' . $fila['apellidos']) ?></td>
                        <td><?= e($fila['curso']) ?></td>
                        <td><?= e(fecha_es((string) $fila['fecha_emision'])) ?></td>
                        <td><span class="badge badge-<?= $fila['estado'] === 'activo' ? 'ok' : 'malo' ?>"><?= $fila['estado'] === 'activo' ? 'Vigente' : 'Anulado' ?></span></td>
                        <td class="acciones">
                            <?php if (!empty($fila['ruta_pdf'])): ?>
                                <a class="btn btn-sec" href="descargar.php?id=<?= (int) $fila['id'] ?>">Descargar</a>
                            <?php endif; ?>
                            <?php if (Auth::esAdmin() && $fila['estado'] === 'activo'): ?>
                                <form method="post" action="index.php?m=certificados&amp;a=anular" data-confirmar="¿Anular este certificado? La verificación pública lo mostrará como anulado.">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $fila['id'] ?>">
                                    <input type="hidden" name="id_curso" value="<?= $curso ? (int) $curso['id'] : (int) $fila['id_curso'] ?>">
                                    <button type="submit" class="btn btn-peligro">Anular</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php html_paginacion((int) $historial['pagina'], (int) $historial['paginas'], (int) $historial['total'], 'hpagina'); ?>
</section>
