<?php

declare(strict_types=1);

class Turno
{
    private array $data;

    public function __construct(array $row)
    {
        $this->data = $row;
    }

    public static function fromRow(?array $row): ?self
    {
        return $row ? new self($row) : null;
    }

    public function getId(): int
    {
        return (int) $this->data['id'];
    }

    public function getCodigo(): string
    {
        return (string) $this->data['codigo'];
    }

    public function getEstado(): string
    {
        return (string) $this->data['estado'];
    }

    public function getPaciente(): ?string
    {
        return $this->data['paciente'] !== null ? (string) $this->data['paciente'] : null;
    }

    public function getEspecialidadId(): int
    {
        return (int) $this->data['especialidad_id'];
    }

    public function getEspecialidadNombre(): string
    {
        return (string) ($this->data['especialidad_nombre'] ?? '');
    }

    public function getMedicoId(): ?int
    {
        return $this->data['medico_id'] !== null ? (int) $this->data['medico_id'] : null;
    }

    public function getConsultorio(): ?string
    {
        $consultorio = $this->data['consultorio'] ?? null;

        if ($consultorio === null || $consultorio === '') {
            $consultorio = $this->data['medico_consultorio'] ?? null;
        }

        return $consultorio !== null && $consultorio !== '' ? (string) $consultorio : null;
    }

    public function getFechaCreacion(): string
    {
        return (string) $this->data['fecha_creacion'];
    }

    public function getFechaLlamado(): ?string
    {
        return $this->data['fecha_llamado'] !== null ? (string) $this->data['fecha_llamado'] : null;
    }

    public function getFechaFin(): ?string
    {
        return $this->data['fecha_fin'] !== null ? (string) $this->data['fecha_fin'] : null;
    }

    public function toArray(): array
    {
        $especialidadId = (int) $this->data['especialidad_id'];
        $medicoId = $this->data['medico_id'] !== null ? (int) $this->data['medico_id'] : null;

        return [
            'id' => $this->getId(),
            'codigo' => $this->getCodigo(),
            'paciente' => $this->getPaciente(),
            'estado' => $this->getEstado(),
            'especialidad' => [
                'id' => $especialidadId,
                'nombre' => $this->data['especialidad_nombre'] ?? '',
                'prefijo' => $this->data['especialidad_prefijo'] ?? '',
            ],
            'medico' => $medicoId === null ? null : [
                'id' => $medicoId,
                'nombre' => $this->data['medico_nombre'] ?? '',
                'consultorio' => $this->getConsultorio(),
            ],
            'consultorio' => $this->getConsultorio(),
            'fecha_creacion' => $this->getFechaCreacion(),
            'fecha_llamado' => $this->getFechaLlamado(),
            'fecha_fin' => $this->getFechaFin(),
        ];
    }
}
