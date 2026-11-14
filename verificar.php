<?php
declare(strict_types=1);
define('SIN_SESION', true);
require __DIR__ . '/libs/bootstrap.php';

$token = (string) ($_GET['token'] ?? '');
$certificado = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $certificado = Certificado::porToken($token);
}

require RAIZ . '/views/verificar/index.php';
