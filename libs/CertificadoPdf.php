<?php

declare(strict_types=1);

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Dompdf\Dompdf;
use Dompdf\Options;

class CertificadoPdf
{
    public static function crear(array $datos, string $destinoPdf): void
    {
        self::comprobarEntorno();
        $directorio = dirname($destinoPdf);
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            throw new AppException('No se pudo preparar la carpeta del certificado.');
        }

        $qrNombre = 'tmp_qr_' . bin2hex(random_bytes(8)) . '.png';
        $qrRuta = RAIZ . '/uploads/certificados/' . $qrNombre;
        try {
            $url = url_publica('verificar.php?token=' . rawurlencode((string) $datos['token_verificacion']));
            self::guardarQr($url, $qrRuta);
            $datos['qr_src'] = 'uploads/certificados/' . $qrNombre;
            self::render(self::html($datos), $destinoPdf);
            self::validarPdf($destinoPdf);
        } finally {
            if (is_file($qrRuta)) {
                unlink($qrRuta);
            }
        }
    }

    private static function comprobarEntorno(): void
    {
        if (!extension_loaded('gd')) {
            throw new AppException('Active la extensión GD de PHP (extension=gd en php.ini) y reinicie Apache.');
        }
        if (!class_exists(Dompdf::class) || !class_exists(QRCode::class)) {
            throw new AppException('Faltan las bibliotecas del certificado. Ejecute composer install en la carpeta del proyecto.');
        }
    }

    private static function guardarQr(string $url, string $destino): void
    {
        $opciones = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'outputBase64' => false,
            'scale' => 6,
        ]);
        $imagen = (new QRCode($opciones))->render($url, $destino);
        if (!is_file($destino) || filesize($destino) < 100) {
            if (is_string($imagen) && str_starts_with($imagen, "\x89PNG")) {
                file_put_contents($destino, $imagen);
            }
        }
        if (!is_file($destino) || filesize($destino) < 100) {
            throw new AppException('No se pudo generar el código QR.');
        }
    }

    private static function render(string $html, string $destinoPdf): void
    {
        $opciones = new Options();
        $opciones->set('isRemoteEnabled', false);
        $opciones->set('isHtml5ParserEnabled', true);
        $opciones->set('isPhpEnabled', false);
        $opciones->set('defaultFont', 'DejaVu Sans');
        $opciones->setChroot(RAIZ);
        $pdf = new Dompdf($opciones);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper([0.0, 0.0, 842.25, 595.5]);
        $pdf->render();
        if (file_put_contents($destinoPdf, $pdf->output()) === false) {
            throw new AppException('No se pudo guardar el PDF del certificado.');
        }
    }

    private static function validarPdf(string $destinoPdf): void
    {
        if (!is_file($destinoPdf) || filesize($destinoPdf) < 1000) {
            throw new AppException('No se pudo crear el archivo PDF del certificado.');
        }
        $archivo = fopen($destinoPdf, 'rb');
        if ($archivo === false) {
            throw new AppException('No se pudo leer el PDF generado.');
        }
        $cabecera = fread($archivo, 5);
        fclose($archivo);
        if ($cabecera !== '%PDF-') {
            unlink($destinoPdf);
            throw new AppException('El archivo generado no es un PDF válido.');
        }
    }

    private static function html(array $d): string
    {
        $baseFrente = 'assets/img/certificado/frente.png';
        $baseReverso = 'assets/img/certificado/reverso.png';
        if (!is_file(RAIZ . '/' . $baseFrente) || !is_file(RAIZ . '/' . $baseReverso)) {
            throw new AppException('Falta la base gráfica del certificado.');
        }

        $nombreEstudiante = mb_strtoupper(trim((string) $d['nombres'] . ' ' . (string) $d['apellidos']), 'UTF-8');
        $documentoLinea = self::lineaDocumento(trim((string) ($d['documento'] ?? '')));
        $cursoLimpio = self::cursoSinPrefijo((string) $d['curso_nombre']);
        $tituloFrente = 'Curso Online: ' . $cursoLimpio;
        $tituloReverso = 'CURSO ONLINE: ' . $cursoLimpio;
        $horas = (int) ($d['duracion_horas'] ?? 0);
        $textoHoras = $horas === 1 ? '1 Hora Teórica - Práctica' : $horas . ' Horas Teóricas - Prácticas';
        $periodo = self::periodo(
            isset($d['fecha_inicio']) ? (string) $d['fecha_inicio'] : '',
            isset($d['fecha_fin']) ? (string) $d['fecha_fin'] : ''
        );
        $codigo = (string) ($d['codigo_certificado'] ?? '');
        $qrSrc = (string) ($d['qr_src'] ?? '');
        $anulado = ($d['estado'] ?? '') === 'anulado';

        $fuenteNombre = RAIZ . '/assets/fonts/Agbalumo-Regular.ttf';
        $fuenteCursiva = RAIZ . '/assets/fonts/FiraSans-BoldItalic.ttf';
        $fuenteNegrita = RAIZ . '/assets/fonts/FiraSans-Bold.ttf';
        $tamanoIdentidad = self::tamanoQueCabe($fuenteNombre, $nombreEstudiante, 32, 620, 15);
        if ($documentoLinea !== '') {
            $tamanoIdentidad = min($tamanoIdentidad, self::tamanoQueCabe($fuenteNombre, $documentoLinea, 32, 620, 15));
        }
        $frase = 'Por participar en el ' . $tituloFrente . ', con ' . $textoHoras;
        $tamanoCuerpo = self::tamanoQueCabe($fuenteCursiva, $frase, 22, 1040, 13);
        $lineasCuerpo = self::lineasParticipacion($tituloFrente, $textoHoras, $tamanoCuerpo, 530);
        $tamanoTitulo = self::tamanoQueCabe($fuenteNegrita, $tituloReverso, 22, 620, 13);
        $topCuerpo = 318;
        $pasoCuerpo = 33;
        $saltoDocumento = round($tamanoIdentidad * 31.5 / 32, 1);

        $modulos = MallaParser::parse((string) ($d['contenido_programatico'] ?? ''));
        $numerados = [];
        foreach ($modulos as $indice => $modulo) {
            $modulo['numero'] = $indice + 1;
            $numerados[] = $modulo;
        }
        [$colIzq, $colDer] = MallaParser::splitColumns($numerados);

        ob_start();
        require RAIZ . '/views/certificados/plantilla.php';

        return (string) ob_get_clean();
    }

    private static function cursoSinPrefijo(string $nombre): string
    {
        $nombre = trim($nombre);
        $limpio = preg_replace('/^curso\s+online\s*:\s*/iu', '', $nombre);
        $limpio = trim((string) $limpio);

        return $limpio !== '' ? $limpio : $nombre;
    }

    private static function lineaDocumento(string $documento): string
    {
        if ($documento === '') {
            return '';
        }
        if (preg_match('/^c\.?\s*i\b/iu', $documento)) {
            return $documento;
        }

        return 'CI: ' . $documento;
    }

    private static function periodo(string $inicio, string $fin): string
    {
        $desde = strtotime($inicio);
        $hasta = strtotime($fin);
        if ($desde === false || $hasta === false) {
            return '';
        }
        $meses = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $mesInicio = $meses[(int) date('n', $desde)];
        $mesFin = $meses[(int) date('n', $hasta)];
        $anioInicio = date('Y', $desde);
        $anioFin = date('Y', $hasta);
        if ($anioInicio === $anioFin && $mesInicio === $mesFin) {
            return $mesInicio . ' del ' . $anioInicio . '.';
        }
        if ($anioInicio === $anioFin) {
            return $mesInicio . ' / ' . $mesFin . ' del ' . $anioInicio . '.';
        }

        return $mesInicio . ' del ' . $anioInicio . ' / ' . $mesFin . ' del ' . $anioFin . '.';
    }

    private static function tamanoQueCabe(string $fuente, string $texto, float $maxTamano, float $maxAncho, float $minTamano): float
    {
        $texto = trim($texto);
        if ($texto === '' || !function_exists('imagettfbbox') || !is_file($fuente)) {
            return $maxTamano;
        }
        $tamano = $maxTamano;
        while ($tamano > $minTamano && self::anchoPdf($fuente, $texto, $tamano) > $maxAncho) {
            $tamano -= 0.5;
        }

        return $tamano;
    }

    /**
     * Parte el párrafo del frente en líneas del ancho del diseño original.
     *
     * @return list<list<array{texto: string, cursiva: bool}>>
     */
    private static function lineasParticipacion(string $titulo, string $horas, float $tamano, float $anchoMax): array
    {
        $bloques = [
            ['Por participar en el', false],
            [$titulo . ',', true],
            ['con ' . $horas, false],
        ];
        $palabras = [];
        foreach ($bloques as [$texto, $cursiva]) {
            foreach (preg_split('/\s+/u', trim($texto)) ?: [] as $palabra) {
                if ($palabra !== '') {
                    $palabras[] = ['texto' => $palabra, 'cursiva' => $cursiva];
                }
            }
        }

        $regular = RAIZ . '/assets/fonts/FiraSans-Regular.ttf';
        $cursivaFuente = RAIZ . '/assets/fonts/FiraSans-BoldItalic.ttf';
        $espacio = self::anchoPdf($regular, 'n n', $tamano) - self::anchoPdf($regular, 'nn', $tamano);
        if ($espacio < 2) {
            $espacio = $tamano * 0.25;
        }

        $lineas = [];
        $actual = [];
        $usado = 0.0;
        foreach ($palabras as $palabra) {
            $fuente = $palabra['cursiva'] ? $cursivaFuente : $regular;
            $ancho = self::anchoPdf($fuente, $palabra['texto'], $tamano);
            $extra = $actual === [] ? 0.0 : $espacio;
            if ($actual !== [] && ($usado + $extra + $ancho) > $anchoMax) {
                $lineas[] = $actual;
                $actual = [];
                $usado = 0.0;
                $extra = 0.0;
            }
            $actual[] = $palabra;
            $usado += $extra + $ancho;
        }
        if ($actual !== []) {
            $lineas[] = $actual;
        }

        return $lineas;
    }

    private static function anchoPdf(string $fuente, string $texto, float $tamano): float
    {
        $caja = imagettfbbox($tamano, 0, $fuente, $texto);
        if ($caja === false) {
            return 0;
        }

        return abs($caja[2] - $caja[0]) * 72 / 96;
    }
}
