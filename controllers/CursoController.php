<?php

declare(strict_types=1);

class CursoController extends Controlador
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
        $estado = (string) ($_GET['estado'] ?? '');
        $this->vista('cursos/index.php', [
            'titulo' => 'Cursos',
            'q' => $q,
            'estado' => in_array($estado, ['activo', 'inactivo'], true) ? $estado : '',
            'listado' => Curso::listar($q, $estado, pagina_solicitada()),
        ]);
    }

    private function guardar(): void
    {
        try {
            $this->exigirPost();
            Auth::exigirAdmin();
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                Curso::actualizar($id, $_POST);
                flash_set('ok', 'Curso actualizado. El código no cambió.');
            } else {
                $codigo = Curso::crear($_POST);
                flash_set('ok', 'Curso registrado con el código ' . $codigo . '.');
            }
            redirigir(url_mod('cursos'));
        } catch (Throwable $e) {
            $this->fallo('modal-curso', $e, 'cursos');
        }
    }

    private function estado(): void
    {
        try {
            $this->exigirPost();
            Auth::exigirAdmin();
            Curso::cambiarEstado((int) ($_POST['id'] ?? 0), (string) ($_POST['estado'] ?? ''));
            flash_set('ok', 'Estado del curso actualizado.');
            redirigir(url_mod('cursos'));
        } catch (Throwable $e) {
            $this->fallo('', $e, 'cursos');
        }
    }
}
