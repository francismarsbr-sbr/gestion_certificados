<?php
$valido = is_array($certificado) && ($certificado['estado'] ?? '') === 'activo';
$anulado = is_array($certificado) && ($certificado['estado'] ?? '') === 'anulado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación de certificado</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="cuerpo-login">
<main class="verificacion">
    <?php if (is_file(RAIZ . '/assets/img/logo.png')): ?>
        <img class="logo-login" src="assets/img/logo.png" alt="">
    <?php endif; ?>
    <p class="eyebrow">Verificación pública</p>
    <h1>Certificado</h1>
    <?php if (!$certificado): ?>
        <p class="alerta alerta-error">No existe un certificado con ese código de verificación.</p>
    <?php else: ?>
        <p class="estado-publico <?= $valido ? 'ok' : 'malo' ?>"><?= $valido ? 'Válido' : 'Anulado' ?></p>
        <dl class="ficha">
            <div><dt>Código</dt><dd class="codigo"><?= e($certificado['codigo_certificado']) ?></dd></div>
            <div><dt>Titular</dt><dd><?= e($certificado['nombres'] . ' ' . $certificado['apellidos']) ?></dd></div>
            <div><dt>Curso</dt><dd><?= e($certificado['curso']) ?></dd></div>
            <div><dt>Duración</dt><dd><?= $certificado['duracion_horas'] ? e((string) $certificado['duracion_horas']) . ' horas académicas' : '—' ?></dd></div>
            <div><dt>Emisión</dt><dd><?= e(fecha_larga((string) $certificado['fecha_emision'])) ?></dd></div>
            <div><dt>Institución</dt><dd><?= e($certificado['institucion'] ?: '—') ?></dd></div>
            <?php if ($anulado && !empty($certificado['fecha_anulacion'])): ?>
                <div><dt>Anulación</dt><dd><?= e(fecha_es((string) $certificado['fecha_anulacion'])) ?></dd></div>
            <?php endif; ?>
        </dl>
    <?php endif; ?>
</main>
</body>
</html>
