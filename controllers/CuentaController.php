<?php

declare(strict_types=1);

class CuentaController extends Controlador
{
    public function manejar(string $accion): void
    {
        if ($accion === 'guardar') {
            $this->guardar();
            return;
        }
        $this->vista('cuenta/index.php', ['titulo' => 'Mi cuenta']);
    }

    private function guardar(): void
    {
        try {
            $this->exigirPost();
            Usuario::cambiarClave(
                (int) ($_SESSION['usuario_id'] ?? 0),
                (string) ($_POST['clave_actual'] ?? ''),
                (string) ($_POST['clave_nueva'] ?? ''),
                (string) ($_POST['clave_confirmacion'] ?? '')
            );
            flash_set('ok', 'La clave fue actualizada.');
            redirigir(url_mod('cuenta'));
        } catch (Throwable $e) {
            $this->fallo('', $e, 'cuenta');
        }
    }
}
