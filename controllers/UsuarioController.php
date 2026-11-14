<?php

declare(strict_types=1);

class UsuarioController extends Controlador
{
    public function manejar(string $accion): void
    {
        if ($accion === 'guardar') {
            $this->guardar();
            return;
        }
        if ($accion === 'estado') {
            $this->estado();
            return;
        }
        $this->indice();
    }

    private function indice(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $this->vista('usuarios/index.php', [
            'titulo' => 'Usuarios',
            'q' => $q,
            'listado' => Usuario::listar($q, pagina_solicitada()),
        ]);
    }

    private function guardar(): void
    {
        try {
            $this->exigirPost();
            Auth::exigirAdmin();
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                Usuario::actualizar($id, $_POST);
                flash_set('ok', 'Usuario actualizado.');
            } else {
                Usuario::crear($_POST);
                flash_set('ok', 'Usuario creado.');
            }
            redirigir(url_mod('usuarios'));
        } catch (Throwable $e) {
            $this->fallo('modal-usuario', $e, 'usuarios');
        }
    }

    private function estado(): void
    {
        try {
            $this->exigirPost();
            Auth::exigirAdmin();
            Usuario::cambiarEstado((int) ($_POST['id'] ?? 0), (string) ($_POST['estado'] ?? ''));
            flash_set('ok', 'Estado del usuario actualizado.');
            redirigir(url_mod('usuarios'));
        } catch (Throwable $e) {
            $this->fallo('', $e, 'usuarios');
        }
    }
}
