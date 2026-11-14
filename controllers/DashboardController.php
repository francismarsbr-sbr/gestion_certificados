<?php

declare(strict_types=1);

class DashboardController extends Controlador
{
    public function manejar(string $accion): void
    {
        $this->vista('dashboard/index.php', [
            'titulo' => 'Dashboard',
            'indicadores' => [
                'cursos' => (int) escalar('SELECT COUNT(*) FROM cursos'),
                'estudiantes' => (int) escalar('SELECT COUNT(*) FROM estudiantes'),
                'inscripciones' => (int) escalar('SELECT COUNT(*) FROM inscripciones'),
                'certificados' => (int) escalar('SELECT COUNT(*) FROM certificados'),
                'vigentes' => (int) escalar("SELECT COUNT(*) FROM certificados WHERE estado = 'activo'"),
            ],
            'recientes' => Certificado::recientes(),
            'incompletos' => Curso::incompletos(),
            'sinCertificado' => Certificado::sinCertificadoActivo(),
        ]);
    }
}
