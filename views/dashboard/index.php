<section class="encabezado-pagina">
    <div>
        <h1>Dashboard</h1>
        <p>Resumen de cursos, estudiantes, inscripciones y certificados.</p>
    </div>
</section>
<div class="tarjetas">
    <a class="tarjeta" href="index.php?m=cursos">
        <span>Total de cursos</span>
        <strong><?= (int) $indicadores['cursos'] ?></strong>
    </a>
    <a class="tarjeta" href="index.php?m=estudiantes">
        <span>Total de estudiantes</span>
        <strong><?= (int) $indicadores['estudiantes'] ?></strong>
    </a>
    <a class="tarjeta" href="index.php?m=inscripciones">
        <span>Total de inscripciones</span>
        <strong><?= (int) $indicadores['inscripciones'] ?></strong>
    </a>
    <a class="tarjeta" href="index.php?m=certificados">
        <span>Certificados emitidos</span>
        <strong><?= (int) $indicadores['certificados'] ?></strong>
        <small><?= (int) $indicadores['vigentes'] ?> vigentes</small>
    </a>
</div>
<?php if ($sinCertificado > 0): ?>
    <p class="aviso">Hay <?= (int) $sinCertificado ?> inscripción<?= $sinCertificado === 1 ? '' : 'es' ?> activa<?= $sinCertificado === 1 ? '' : 's' ?> sin certificado vigente. <a href="index.php?m=certificados">Ir a certificados</a></p>
<?php endif; ?>
<?php if ($incompletos !== []): ?>
    <section class="panel">
        <h2>Cursos por completar</h2>
        <p class="ayuda">Antes de emitir un certificado, el curso necesita fechas, duración, facilitador e institución. Esos datos no venían cargados.</p>
        <ul class="lista-simple">
            <?php foreach ($incompletos as $curso): ?>
                <li><span class="codigo"><?= e($curso['codigo_curso']) ?></span> <?= e($curso['nombre']) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php if (Auth::esAdmin()): ?>
            <a class="btn btn-pri" href="index.php?m=cursos">Completar cursos</a>
        <?php endif; ?>
    </section>
<?php endif; ?>
<section class="panel">
    <h2>Últimos certificados</h2>
    <?php if ($recientes === []): ?>
        <p class="vacio">Todavía no se han emitido certificados.</p>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr><th>Código</th><th>Estudiante</th><th>Curso</th><th>Emisión</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recientes as $fila): ?>
                        <tr>
                            <td class="codigo"><?= e($fila['codigo_certificado']) ?></td>
                            <td><?= e($fila['nombres'] . ' ' . $fila['apellidos']) ?></td>
                            <td><?= e($fila['curso']) ?></td>
                            <td><?= e(fecha_es((string) $fila['fecha_emision'])) ?></td>
                            <td><span class="badge badge-<?= $fila['estado'] === 'activo' ? 'ok' : 'malo' ?>"><?= $fila['estado'] === 'activo' ? 'Vigente' : 'Anulado' ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
