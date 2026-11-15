<?php

declare(strict_types=1);

class GestorColaTurnos
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function emitirTurno(int $especialidadId, ?string $paciente = null): Turno
    {
        $especialidad = Especialidad::find($especialidadId);
        if (!$especialidad) {
            throw new InvalidArgumentException('La especialidad seleccionada no existe.');
        }

        $paciente = $paciente !== null ? trim($paciente) : null;
        if ($paciente === '') {
            $paciente = null;
        }

        if ($paciente !== null && mb_strlen($paciente) > 120) {
            throw new InvalidArgumentException('El nombre del paciente no puede superar 120 caracteres.');
        }

        return (new GeneradorCodigoTurno($this->pdo))->emitir($especialidad, $paciente);
    }

    public function llamarSiguiente(int $medicoId, ?int $especialidadId = null): ?Turno
    {
        $medico = Usuario::findById($medicoId);

        if (!$medico || $medico->getRol() !== 'medico' || !$medico->isActivo()) {
            throw new InvalidArgumentException('El médico seleccionado no es válido o está inactivo.');
        }

        $espId = $medico->getEspecialidadId() ?: $especialidadId;
        if (!$espId) {
            throw new InvalidArgumentException('El médico no tiene una especialidad configurada.');
        }

        $dia = date('Y-m-d');
        $ahora = date('Y-m-d H:i:s');

        $this->begin();

        try {
            $stmt = $this->pdo->prepare(
                $this->baseSelect() . "
                 WHERE t.dia = ? AND t.estado = 'ESPERA' AND t.especialidad_id = ?
                 ORDER BY t.fecha_creacion ASC, t.id ASC
                 LIMIT 1"
            );
            $stmt->execute([$dia, $espId]);
            $row = $stmt->fetch();

            if (!$row) {
                $this->commit();
                return null;
            }

            $fin = $this->pdo->prepare(
                "UPDATE turnos
                 SET estado = 'ATENDIDO', fecha_fin = ?
                 WHERE medico_id = ? AND estado = 'ATENDIENDO' AND dia = ?"
            );
            $fin->execute([$ahora, $medicoId, $dia]);

            $update = $this->pdo->prepare(
                "UPDATE turnos
                 SET estado = 'ATENDIENDO', medico_id = ?, consultorio = ?, fecha_llamado = ?
                 WHERE id = ? AND estado = 'ESPERA'"
            );
            $update->execute([$medicoId, $medico->getConsultorio(), $ahora, $row['id']]);

            if ($update->rowCount() !== 1) {
                throw new RuntimeException('El turno ya fue tomado por otro usuario. Intente nuevamente.');
            }

            $this->commit();

            return $this->obtenerTurno((int) $row['id']);
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function marcarAusente(int $turnoId, int $medicoId): array
    {
        $turno = $this->obtenerTurno($turnoId);

        if (!$turno || $turno->getEstado() !== 'ATENDIENDO') {
            throw new InvalidArgumentException('El turno no está siendo atendido.');
        }

        if ($turno->getMedicoId() !== $medicoId) {
            throw new InvalidArgumentException('El turno no pertenece al médico seleccionado.');
        }

        $stmt = $this->pdo->prepare(
            "UPDATE turnos
             SET estado = 'AUSENTE', fecha_fin = ?
             WHERE id = ? AND estado = 'ATENDIENDO'"
        );
        $stmt->execute([date('Y-m-d H:i:s'), $turnoId]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('No se pudo actualizar el turno.');
        }

        $siguiente = $this->llamarSiguiente($medicoId);

        return [
            'ausente' => $this->obtenerTurno($turnoId),
            'siguiente' => $siguiente,
        ];
    }

    public function finalizar(int $turnoId, int $medicoId): Turno
    {
        $turno = $this->obtenerTurno($turnoId);

        if (!$turno || $turno->getEstado() !== 'ATENDIENDO') {
            throw new InvalidArgumentException('El turno no está siendo atendido.');
        }

        if ($turno->getMedicoId() !== $medicoId) {
            throw new InvalidArgumentException('El turno no pertenece al médico seleccionado.');
        }

        $stmt = $this->pdo->prepare(
            "UPDATE turnos
             SET estado = 'ATENDIDO', fecha_fin = ?
             WHERE id = ? AND estado = 'ATENDIENDO'"
        );
        $stmt->execute([date('Y-m-d H:i:s'), $turnoId]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('No se pudo finalizar el turno.');
        }

        return $this->obtenerTurno($turnoId);
    }

    public function turnoActualDeMedico(int $medicoId): ?Turno
    {
        $stmt = $this->pdo->prepare(
            $this->baseSelect() . "
             WHERE t.estado = 'ATENDIENDO' AND t.dia = ? AND t.medico_id = ?
             ORDER BY t.fecha_llamado DESC, t.id DESC
             LIMIT 1"
        );
        $stmt->execute([date('Y-m-d'), $medicoId]);

        return Turno::fromRow($stmt->fetch() ?: null);
    }

    public function colaDeEspecialidad(?int $especialidadId = null, int $limite = 100): array
    {
        $sql = $this->baseSelect() . "
                WHERE t.estado = 'ESPERA' AND t.dia = ?";
        $params = [date('Y-m-d')];

        if ($especialidadId !== null) {
            $sql .= ' AND t.especialidad_id = ?';
            $params[] = $especialidadId;
        }

        $sql .= ' ORDER BY t.fecha_creacion ASC, t.id ASC LIMIT ' . max(1, min($limite, 200));

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map(static fn (array $row): Turno => new Turno($row), $stmt->fetchAll());
    }

    public function salaEspera(): array
    {
        $dia = date('Y-m-d');

        $stmt = $this->pdo->prepare(
            $this->baseSelect() . "
             WHERE t.estado = 'ATENDIENDO' AND t.dia = ?
             ORDER BY t.fecha_llamado ASC, t.id ASC
             LIMIT 12"
        );
        $stmt->execute([$dia]);
        $atendiendo = array_map(
            static fn (array $row): array => (new Turno($row))->toArray(),
            $stmt->fetchAll()
        );

        $stmt = $this->pdo->prepare(
            $this->baseSelect() . "
             WHERE t.estado = 'ESPERA' AND t.dia = ?
             ORDER BY t.fecha_creacion ASC, t.id ASC
             LIMIT 12"
        );
        $stmt->execute([$dia]);
        $proximos = array_map(
            static fn (array $row): array => (new Turno($row))->toArray(),
            $stmt->fetchAll()
        );

        return [
            'atendiendo' => $atendiendo,
            'proximos' => $proximos,
            'actualizado' => date('c'),
            'hoy' => $dia,
        ];
    }

    public function historial(string $dia, ?int $especialidadId = null, ?string $estado = null): array
    {
        $estados = ['ESPERA', 'ATENDIENDO', 'ATENDIDO', 'AUSENTE'];
        if ($estado !== null && !in_array($estado, $estados, true)) {
            throw new InvalidArgumentException('Estado de turno no válido.');
        }

        $sql = $this->baseSelect() . ' WHERE t.dia = ?';
        $params = [$dia];

        if ($especialidadId !== null) {
            $sql .= ' AND t.especialidad_id = ?';
            $params[] = $especialidadId;
        }

        if ($estado !== null) {
            $sql .= ' AND t.estado = ?';
            $params[] = $estado;
        }

        $sql .= ' ORDER BY t.fecha_creacion DESC, t.id DESC LIMIT 500';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map(static fn (array $row): Turno => new Turno($row), $stmt->fetchAll());
    }

    public function resumenDia(string $dia): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT estado, COUNT(*) AS total FROM turnos WHERE dia = ? GROUP BY estado'
        );
        $stmt->execute([$dia]);

        $resumen = [
            'total' => 0,
            'ESPERA' => 0,
            'ATENDIENDO' => 0,
            'ATENDIDO' => 0,
            'AUSENTE' => 0,
        ];

        foreach ($stmt->fetchAll() as $row) {
            $total = (int) $row['total'];
            $resumen[$row['estado']] = $total;
            $resumen['total'] += $total;
        }

        return $resumen;
    }

    public function obtenerTurno(int $id): ?Turno
    {
        $stmt = $this->pdo->prepare($this->baseSelect() . ' WHERE t.id = ?');
        $stmt->execute([$id]);

        return Turno::fromRow($stmt->fetch() ?: null);
    }

    private function baseSelect(): string
    {
        return "SELECT t.*, e.nombre AS especialidad_nombre, e.prefijo AS especialidad_prefijo,
                       u.nombre AS medico_nombre,
                       COALESCE(t.consultorio, u.consultorio) AS medico_consultorio
                FROM turnos t
                JOIN especialidades e ON e.id = t.especialidad_id
                LEFT JOIN usuarios u ON u.id = t.medico_id";
    }

    private function begin(): void
    {
        $this->pdo->exec('BEGIN IMMEDIATE');
    }

    private function commit(): void
    {
        $this->pdo->exec('COMMIT');
    }

    private function rollback(): void
    {
        try {
            $this->pdo->exec('ROLLBACK');
        } catch (PDOException) {
        }
    }
}
