<?php

declare(strict_types=1);

class CertificadoController extends Controlador
{
    public function manejar(string $accion): void
    {
        if ($accion === 'generar') {
            $this->generar();
            return;
        }
        if ($accion === 'anular') {
            $this->anular();
            return;
        }
        $this->indice();
    }

    private function indice(): void
    {
        $cursoId = (int) ($_GET['curso'] ?? 0);
        $q = trim((string) ($_GET['q'] ?? ''));
        $curso = $cursoId > 0 ? Curso::porId($cursoId) : null;
        $estado = (string) ($_GET['estado'] ?? '');
        $cursoHistorial = (int) ($_GET['curso_h'] ?? 0);
        $this->vista('certificados/index.php', [
            'titulo' => 'Certificados',
            'cursos' => Curso::opciones(),
            'curso' => $curso,
            'q' => $q,
            'resumen' => $curso ? Inscripcion::resumen((int) $curso['id']) : null,
            'listado' => $curso ? Inscripcion::listarPorCurso((int) $curso['id'], $q, pagina_solicitada()) : null,
            'hq' => trim((string) ($_GET['hq'] ?? '')),
            'estado' => in_array($estado, ['activo', 'anulado'], true) ? $estado : '',
            'cursoHistorial' => $cursoHistorial,
            'historial' => Certificado::listar(
                trim((string) ($_GET['hq'] ?? '')),
                $estado,
                $cursoHistorial,
                pagina_solicitada('hpagina')
            ),
        ]);
    }

    private function generar(): void
    {
        $curso = (int) ($_POST['id_curso'] ?? 0);
        try {
            $this->exigirPost();
            $resultado = Certificado::generar((int) ($_POST['id_inscripcion'] ?? 0));
            flash_set('ok', 'Certificado generado: ' . $resultado['codigo'] . '.');
            redirigir(url_mod('certificados', ['curso' => $resultado['id_curso']]));
        } catch (Throwable $e) {
            $this->fallo('', $e, 'certificados', $curso > 0 ? ['curso' => $curso] : []);
        }
    }

    private function anular(): void
    {
        $curso = (int) ($_POST['id_curso'] ?? 0);
        try {
            $this->exigirPost();
            Auth::exigirAdmin();
            $resultado = Certificado::anular((int) ($_POST['id'] ?? 0));
            flash_set('ok', 'Certificado ' . $resultado['codigo'] . ' anulado. La verificación pública lo refleja.');
            $destino = $resultado['id_curso'] > 0 ? $resultado['id_curso'] : $curso;
            redirigir(url_mod('certificados', $destino > 0 ? ['curso' => $destino] : []));
        } catch (Throwable $e) {
            $this->fallo('', $e, 'certificados', $curso > 0 ? ['curso' => $curso] : []);
        }
    }
}
