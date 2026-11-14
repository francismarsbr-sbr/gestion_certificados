<?php
declare(strict_types=1);
require __DIR__ . '/libs/bootstrap.php';
Auth::exigirLogin();
Certificado::descargar((int) ($_GET['id'] ?? 0));
