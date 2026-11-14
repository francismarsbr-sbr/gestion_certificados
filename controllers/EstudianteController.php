<?php

declare(strict_types=1);

class EstudianteController extends Controlador
{
    public function manejar(string $accion): void
    {
        if ($accion === 'guardar') {
            $this->guardar();
            return;
        }
        $this->indice();
    }

    private function indice(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $this->vista('estudiantes/index.php', [
            'titulo' => 'Estudiantes',
            'q' => $q,
            'listado' => Estudiante::listar($q, pagina_solicitada()),
        ]);
    }

    private function guardar(): void
    {
        try {
            $this->exigirPost();
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                Estudiante::actualizar($id, $_POST);
                flash_set('ok', 'Datos del estudiante actualizados.');
            } else {
                Estudiante::crear($_POST);
                flash_set('ok', 'Estudiante registrado.');
            }
            redirigir(url_mod('estudiantes'));
        } catch (Throwable $e) {
            $this->fallo('modal-estudiante', $e, 'estudiantes');
        }
    }
}
