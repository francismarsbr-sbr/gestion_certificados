<?php
if (!function_exists('render_modulos_certificado')) {
    function render_modulos_certificado(array $modulos): void
    {
        foreach ($modulos as $modulo) {
            $titulo = trim((string) ($modulo['title'] ?? ''));
            $numero = (int) ($modulo['numero'] ?? 0);
            if ($titulo === '') {
                $titulo = 'Módulo ' . $numero;
            }
            echo '<div class="modulo"><table><tr><td class="mod-titulo" colspan="2">' . e($numero . '. ' . $titulo) . '</td></tr>';
            foreach ($modulo['items'] as $item) {
                echo '<tr><td class="vineta">•</td><td class="mod-item">' . e((string) $item) . '</td></tr>';
            }
            echo '</table></div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado <?= e($codigo) ?></title>
    <style>
        @font-face {
            font-family: "Fira Sans";
            font-style: normal;
            font-weight: normal;
            src: url("assets/fonts/FiraSans-Regular.ttf") format("truetype");
        }
        @font-face {
            font-family: "Fira Sans";
            font-style: normal;
            font-weight: bold;
            src: url("assets/fonts/FiraSans-Bold.ttf") format("truetype");
        }
        @font-face {
            font-family: "Fira Sans";
            font-style: italic;
            font-weight: bold;
            src: url("assets/fonts/FiraSans-BoldItalic.ttf") format("truetype");
        }
        @font-face {
            font-family: "Agbalumo";
            font-style: normal;
            font-weight: normal;
            src: url("assets/fonts/Agbalumo-Regular.ttf") format("truetype");
        }
        @page { margin: 0; }
        * { margin: 0; padding: 0; }
        body { font-family: "Fira Sans", sans-serif; color: #000; }
        .hoja {
            position: relative;
            width: 842.25pt;
            height: 595.5pt;
            overflow: hidden;
            page-break-after: always;
        }
        .hoja-ultima { page-break-after: auto; }
        .fondo { position: absolute; left: 0; top: 0; width: 842.25pt; height: 595.5pt; }
        .identidad { position: absolute; left: 110pt; top: 208pt; width: 622pt; }
        .nombre, .doc {
            position: absolute;
            left: 0;
            width: 622pt;
            text-align: center;
            font-family: "Agbalumo", sans-serif;
            font-size: <?= $tamanoIdentidad ?>pt;
            line-height: <?= $tamanoIdentidad ?>pt;
            color: #000;
        }
        .nombre { top: 0; }
        .doc { top: <?= $saltoDocumento ?>pt; }
        .linea {
            position: absolute;
            left: 70pt;
            width: 702pt;
            text-align: center;
            font-family: "Fira Sans", sans-serif;
            font-weight: normal;
            font-size: <?= $tamanoCuerpo ?>pt;
            line-height: <?= $tamanoCuerpo + 4 ?>pt;
            color: #000;
        }
        .curso { font-family: "Fira Sans", sans-serif; font-weight: bold; font-style: italic; }
        .sep { display: inline-block; width: <?= round($tamanoCuerpo * 0.25, 2) ?>pt; }
        .qr { position: absolute; left: 52pt; top: 468pt; width: 58pt; height: 58pt; }
        .codigo {
            position: absolute;
            left: 40pt;
            top: 528pt;
            width: 84pt;
            text-align: center;
            font-family: "Fira Sans", sans-serif;
            font-size: 7pt;
            color: #222;
        }
        .sello {
            position: absolute;
            left: 175pt;
            top: 448pt;
            width: 492pt;
            text-align: center;
            font-family: "Fira Sans", sans-serif;
            font-weight: bold;
            font-size: 16pt;
            letter-spacing: 0.6pt;
            color: #9f1239;
            border: 2pt solid #9f1239;
            padding: 3pt 0;
        }
        .titulo-reverso {
            position: absolute;
            left: 130pt;
            top: 126pt;
            width: 582pt;
            text-align: center;
            font-family: "Fira Sans", sans-serif;
            font-weight: bold;
            font-size: <?= $tamanoTitulo ?>pt;
            line-height: <?= $tamanoTitulo + 4 ?>pt;
            color: #000;
        }
        .malla {
            position: absolute;
            left: 64pt;
            top: 212pt;
            width: 714pt;
            border-collapse: collapse;
        }
        .columna { width: 357pt; vertical-align: top; }
        .modulo { margin-bottom: 8pt; }
        .modulo table { width: 100%; border-collapse: collapse; }
        .mod-titulo {
            font-family: "Fira Sans", sans-serif;
            font-weight: bold;
            font-size: 12pt;
            line-height: 15pt;
            color: #9F2BFC;
            padding: 0 8pt 1pt 4pt;
        }
        .vineta {
            width: 14pt;
            font-family: "Fira Sans", sans-serif;
            font-size: 11pt;
            line-height: 15.5pt;
            color: #000;
            vertical-align: top;
            padding-left: 18pt;
        }
        .mod-item {
            font-family: "Fira Sans", sans-serif;
            font-weight: normal;
            font-size: 12pt;
            line-height: 15.5pt;
            color: #000;
            vertical-align: top;
            padding-right: 12pt;
        }
        .sello-reverso { top: 492pt; }
    </style>
</head>
<body>
<div class="hoja">
    <img class="fondo" src="<?= e($baseFrente) ?>" alt="">
    <div class="identidad">
        <div class="nombre"><?= e($nombreEstudiante) ?></div>
        <?php if ($documentoLinea !== ''): ?>
            <div class="doc"><?= e($documentoLinea) ?></div>
        <?php endif; ?>
    </div>
    <?php foreach ($lineasCuerpo as $indice => $linea): ?>
        <div class="linea" style="top: <?= $topCuerpo + ($indice * $pasoCuerpo) ?>pt;">
            <?php foreach ($linea as $j => $parte): ?>
                <?php if ($j > 0): ?><span class="sep">&#160;</span><?php endif; ?><span class="<?= $parte['cursiva'] ? 'curso' : 'txt' ?>"><?= e($parte['texto']) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    <?php if ($periodo !== ''): ?>
        <div class="linea" style="top: <?= $topCuerpo + (count($lineasCuerpo) * $pasoCuerpo) ?>pt;"><?= e($periodo) ?></div>
    <?php endif; ?>
    <?php if ($qrSrc !== ''): ?>
        <img class="qr" src="<?= e($qrSrc) ?>" alt="">
    <?php endif; ?>
    <?php if ($codigo !== ''): ?>
        <div class="codigo"><?= e($codigo) ?></div>
    <?php endif; ?>
    <?php if ($anulado): ?>
        <div class="sello">DOCUMENTO ANULADO — SIN VALIDEZ</div>
    <?php endif; ?>
</div>
<div class="hoja hoja-ultima">
    <img class="fondo" src="<?= e($baseReverso) ?>" alt="">
    <div class="titulo-reverso"><?= e($tituloReverso) ?></div>
    <?php if ($colIzq !== [] || $colDer !== []): ?>
        <table class="malla">
            <tr>
                <td class="columna"><?php render_modulos_certificado($colIzq); ?></td>
                <td class="columna"><?php render_modulos_certificado($colDer); ?></td>
            </tr>
        </table>
    <?php endif; ?>
    <?php if ($anulado): ?>
        <div class="sello sello-reverso">DOCUMENTO ANULADO — SIN VALIDEZ</div>
    <?php endif; ?>
</div>
</body>
</html>
