<?php

declare(strict_types=1);

class InscripcionController extends Controlador
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
        $cursoId = (int) ($_GET['curso'] ?? 0);
        $q = trim((string) ($_GET['q'] ?? ''));
        $curso = $cursoId > 0 ? Curso::porId($cursoId) : null;
        $this->vista('inscripciones/index.php', [
            'titulo' => 'Inscripciones',
            'cursos' => Curso::opciones(),
            'curso' => $curso,
            'q' => $q,
            'resumen' => $curso ? Inscripcion::resumen((int) $curso['id']) : null,
            'listado' => $curso ? Inscripcion::listarPorCurso((int) $curso['id'], '', pagina_solicitada()) : null,
            'coincidencias' => ($curso && $q !== '') ? Estudiante::buscarParaCurso($q, (int) $curso['id']) : [],
        ]);
    }

    private function guardar(): void
    {
        $cursoId = (int) ($_POST['id_curso'] ?? 0);
        try {
            $this->exigirPost();
            Inscripcion::crear(
                (int) ($_POST['id_estudiante'] ?? 0),
                $cursoId,
                (string) ($_POST['fecha_inscripcion'] ?? '')
            );
            flash_set('ok', 'Inscripción registrada.');
            redirigir(url_mod('inscripciones', ['curso' => $cursoId]));
        } catch (Throwable $e) {
            $this->fallo('', $e, 'inscripciones', $cursoId > 0 ? ['curso' => $cursoId] : []);
        }
    }
}
