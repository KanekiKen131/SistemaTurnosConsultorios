<?php

declare(strict_types=1);

class GeneradorCodigoTurno
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function emitir(Especialidad $especialidad, ?string $paciente = null): Turno
    {
        $intentos = 0;

        while ($intentos < 8) {
            $intentos++;
            $inTransaction = false;

            try {
                $this->pdo->exec('BEGIN IMMEDIATE');
                $inTransaction = true;

                $dia = date('Y-m-d');
                $stmt = $this->pdo->prepare(
                    'SELECT COALESCE(MAX(secuencial), 0) + 1
                     FROM turnos
                     WHERE dia = ? AND especialidad_id = ?'
                );
                $stmt->execute([$dia, $especialidad->getId()]);
                $secuencial = (int) $stmt->fetchColumn();
                $codigo = $especialidad->getPrefijo() . '-' . str_pad((string) $secuencial, 2, '0', STR_PAD_LEFT);

                $insert = $this->pdo->prepare(
                    "INSERT INTO turnos
                        (codigo, dia, secuencial, paciente, especialidad_id, estado, fecha_creacion)
                     VALUES (?, ?, ?, ?, ?, 'ESPERA', ?)"
                );
                $insert->execute([
                    $codigo,
                    $dia,
                    $secuencial,
                    $paciente,
                    $especialidad->getId(),
                    date('Y-m-d H:i:s'),
                ]);

                $id = (int) $this->pdo->lastInsertId();

                $this->pdo->exec('COMMIT');
                $inTransaction = false;

                return $this->find($id);
            } catch (PDOException $e) {
                if ($inTransaction) {
                    try {
                        $this->pdo->exec('ROLLBACK');
                    } catch (PDOException) {
                    }
                }

                if (str_contains($e->getMessage(), 'UNIQUE') && $intentos < 8) {
                    usleep(50000 * $intentos);
                    continue;
                }

                throw new RuntimeException('No se pudo generar el turno: ' . $e->getMessage(), 0, $e);
            }
        }

        throw new RuntimeException('No se pudo generar un código único. Intente nuevamente.');
    }

    private function find(int $id): Turno
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.*, e.nombre AS especialidad_nombre, e.prefijo AS especialidad_prefijo,
                    u.nombre AS medico_nombre, COALESCE(t.consultorio, u.consultorio) AS medico_consultorio
             FROM turnos t
             JOIN especialidades e ON e.id = t.especialidad_id
             LEFT JOIN usuarios u ON u.id = t.medico_id
             WHERE t.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('El turno generado no pudo ser recuperado.');
        }

        return new Turno($row);
    }
}
