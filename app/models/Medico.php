<?php

declare(strict_types=1);

require_once __DIR__ . '/Usuario.php';

class Medico extends Usuario
{
    public function puede(string $accion): bool
    {
        return in_array($accion, ['llamar', 'ausente', 'finalizar', 'ver_historial'], true);
    }

    public function getEspecialidadId(): ?int
    {
        return $this->especialidadId;
    }

    public function getConsultorio(): ?string
    {
        return $this->consultorio;
    }
}
