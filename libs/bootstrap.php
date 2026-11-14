<?php

declare(strict_types=1);

define('RAIZ', dirname(__DIR__));

require RAIZ . '/config/config.php';

date_default_timezone_set(APP_TIMEZONE);
mb_internal_encoding('UTF-8');

if (!APP_DEBUG) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

$autoload = RAIZ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}

require RAIZ . '/libs/AppException.php';
require RAIZ . '/libs/helpers.php';
require RAIZ . '/config/Conexion.php';
require RAIZ . '/libs/Validador.php';
require RAIZ . '/libs/Codigos.php';
require RAIZ . '/libs/MallaParser.php';
require RAIZ . '/models/Usuario.php';
require RAIZ . '/models/Curso.php';
require RAIZ . '/models/Estudiante.php';
require RAIZ . '/models/Inscripcion.php';
require RAIZ . '/models/Certificado.php';
require RAIZ . '/libs/Auth.php';
require RAIZ . '/libs/Controlador.php';
require RAIZ . '/libs/CertificadoPdf.php';
require RAIZ . '/controllers/DashboardController.php';
require RAIZ . '/controllers/CursoController.php';
require RAIZ . '/controllers/EstudianteController.php';
require RAIZ . '/controllers/InscripcionController.php';
require RAIZ . '/controllers/CertificadoController.php';
require RAIZ . '/controllers/UsuarioController.php';
require RAIZ . '/controllers/CuentaController.php';

if (PHP_SAPI !== 'cli') {
    cabeceras_seguridad();
    if (!defined('SIN_SESION')) {
        iniciar_sesion();
    }
}

try {
    Conexion::obtener();
} catch (Throwable $e) {
    registrar_error($e);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "No se pudo conectar a MySQL. Importe database/gestion_certificados.sql y revise config/config.php.\n");
        exit(1);
    }
    http_response_code(500);
    require RAIZ . '/views/error_conexion.php';
    exit;
}
