<?php

declare(strict_types=1);

class Controlador
{
    protected function exigirPost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            throw new AppException('La acción no es válida.');
        }
        csrf_verificar();
    }

    protected function fallo(string $modal, Throwable $e, string $modulo, array $extra = []): void
    {
        if ($e instanceof AppException) {
            guardar_reintento($modal !== '' ? $modal : null, $_POST);
            flash_set('error', $e->getMessage());
        } else {
            registrar_error($e);
            flash_set('error', 'No se pudo completar la operación.');
        }
        redirigir(url_mod($modulo, $extra));
    }

    protected function vista(string $ruta, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        require RAIZ . '/views/parciales/cabecera.php';
        require RAIZ . '/views/' . $ruta;
        require RAIZ . '/views/parciales/pie.php';
    }
}
