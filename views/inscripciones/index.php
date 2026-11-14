<section class="encabezado-pagina">
    <div>
        <h1>Inscripciones</h1>
        <p>Asocie un estudiante ya registrado con un curso. Una persona no puede inscribirse dos veces en el mismo curso.</p>
    </div>
</section>
<form class="filtros" method="get" action="index.php">
    <input type="hidden" name="m" value="inscripciones">
    <label class="campo campo-linea">
        <span>Curso</span>
        <select name="curso" data-enviar>
            <option value="">Seleccione un curso</option>
            <?php foreach ($cursos as $opcion): ?>
                <option value="<?= (int) $opcion['id'] ?>"<?= $curso && (int) $curso['id'] === (int) $opcion['id'] ? ' selected' : '' ?>>
                    <?= e($opcion['codigo_curso'] . ' · ' . $opcion['nombre']) ?><?= $opcion['estado'] !== 'activo' ? ' (inactivo)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
</form>
<?php if (!$curso): ?>
    <p class="vacio">Seleccione un curso para ver sus participantes.</p>
<?php else: ?>
    <div class="resumen-linea">
        <span><strong><?= (int) $resumen['total'] ?></strong> participantes</span>
        <span><strong><?= (int) $resumen['con_certificado'] ?></strong> con certificado</span>
        <span><strong><?= (int) $resumen['sin_certificado'] ?></strong> sin certificado</span>
    </div>
    <form class="filtros" method="get" action="index.php">
        <input type="hidden" name="m" value="inscripciones">
        <input type="hidden" name="curso" value="<?= (int) $curso['id'] ?>">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar estudiante por nombre o documento" maxlength="120">
        <button type="submit" class="btn btn-sec">Buscar</button>
    </form>
    <?php if ($q !== ''): ?>
        <div class="tabla-contenedor">
            <table>
                <thead><tr><th>Estudiante</th><th>Documento</th><th>Inscripción</th></tr></thead>
                <tbody>
                    <?php if ($coincidencias === []): ?>
                        <tr><td colspan="3" class="vacio">Ningún estudiante coincide con la búsqueda.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($coincidencias as $est): ?>
                        <tr>
                            <td><?= e($est['apellidos'] . ', ' . $est['nombres']) ?></td>
                            <td class="codigo"><?= e($est['documento']) ?></td>
                            <td>
                                <?php if ($est['inscripcion_id']): ?>
                                    <span class="badge badge-ok">Ya inscrito</span>
                                <?php elseif ($curso['estado'] !== 'activo'): ?>
                                    <span class="badge badge-neutro">Curso inactivo</span>
                                <?php else: ?>
                                    <form method="post" action="index.php?m=inscripciones&amp;a=guardar" class="acciones">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="id_curso" value="<?= (int) $curso['id'] ?>">
                                        <input type="hidden" name="id_estudiante" value="<?= (int) $est['id'] ?>">
                                        <input type="date" name="fecha_inscripcion" required value="<?= e(old('fecha_inscripcion', date('Y-m-d'))) ?>">
                                        <button type="submit" class="btn btn-pri">Inscribir</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="ayuda">Escriba un nombre o documento para inscribir a alguien que ya esté registrado.</p>
    <?php endif; ?>
    <h2>Participantes del curso</h2>
    <div class="tabla-contenedor">
        <table>
            <thead><tr><th>Estudiante</th><th>Documento</th><th>Inscripción</th><th>Certificado</th></tr></thead>
            <tbody>
                <?php if ($listado['filas'] === []): ?>
                    <tr><td colspan="4" class="vacio">Este curso todavía no tiene inscritos.</td></tr>
                <?php endif; ?>
                <?php foreach ($listado['filas'] as $fila): ?>
                    <tr>
                        <td><?= e($fila['apellidos'] . ', ' . $fila['nombres']) ?></td>
                        <td class="codigo"><?= e($fila['documento']) ?></td>
                        <td><?= e(fecha_es((string) $fila['fecha_inscripcion'])) ?></td>
                        <td>
                            <?php if ($fila['certificado_id']): ?>
                                <span class="badge badge-ok">Generado</span>
                                <span class="codigo"><?= e($fila['codigo_certificado']) ?></span>
                            <?php else: ?>
                                <span class="badge badge-aviso">Pendiente</span>
                                <?php if ((int) $fila['anulados'] > 0): ?><small>Tiene anulados</small><?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php html_paginacion((int) $listado['pagina'], (int) $listado['paginas'], (int) $listado['total']); ?>
<?php endif; ?>
