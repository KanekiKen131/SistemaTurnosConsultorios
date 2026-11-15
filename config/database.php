<?php

declare(strict_types=1);

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/Mexico_City');

class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dbPath = getenv('DB_PATH') ?: __DIR__ . '/../database.sqlite';

        try {
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA busy_timeout = 5000');
            $pdo->exec('PRAGMA journal_mode = WAL');

            self::$pdo = $pdo;
            self::migrate($pdo);
            self::seed($pdo);

            return self::$pdo;
        } catch (PDOException $e) {
            throw new RuntimeException('No se pudo conectar a la base de datos: ' . $e->getMessage(), 0, $e);
        }
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS especialidades (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE,
                prefijo TEXT NOT NULL UNIQUE
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                rol TEXT NOT NULL CHECK (rol IN ('admin', 'recepcion', 'medico')),
                activo INTEGER NOT NULL DEFAULT 1,
                especialidad_id INTEGER,
                consultorio TEXT,
                creado_en TEXT NOT NULL,
                FOREIGN KEY (especialidad_id) REFERENCES especialidades(id)
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS turnos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo TEXT NOT NULL,
                dia TEXT NOT NULL,
                secuencial INTEGER NOT NULL,
                paciente TEXT,
                especialidad_id INTEGER NOT NULL,
                medico_id INTEGER,
                consultorio TEXT,
                estado TEXT NOT NULL DEFAULT 'ESPERA'
                    CHECK (estado IN ('ESPERA', 'ATENDIENDO', 'ATENDIDO', 'AUSENTE')),
                fecha_creacion TEXT NOT NULL,
                fecha_llamado TEXT,
                fecha_fin TEXT,
                FOREIGN KEY (especialidad_id) REFERENCES especialidades(id),
                FOREIGN KEY (medico_id) REFERENCES usuarios(id)
            )"
        );

        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_turnos_codigo_dia ON turnos (codigo, dia)');
        $pdo->exec(
            "CREATE UNIQUE INDEX IF NOT EXISTS ux_turnos_medico_atendiendo
             ON turnos (medico_id) WHERE estado = 'ATENDIENDO'"
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS ix_turnos_dia_estado ON turnos (dia, estado)');
        $pdo->exec(
            'CREATE INDEX IF NOT EXISTS ix_turnos_cola
             ON turnos (dia, estado, especialidad_id, fecha_creacion)'
        );
    }

    private static function seed(PDO $pdo): void
    {
        $totalEspecialidades = (int) $pdo->query('SELECT COUNT(*) FROM especialidades')->fetchColumn();

        $especialidades = [
            ['Medicina general', 'MG'],
            ['Pediatría', 'PEDI'],
            ['Medicina interna', 'MI'],
            ['Odontología', 'OD'],
            ['Psicología', 'PSI'],
        ];

        if ($totalEspecialidades === 0) {
            $insert = $pdo->prepare('INSERT INTO especialidades (nombre, prefijo) VALUES (?, ?)');
            foreach ($especialidades as [$nombre, $prefijo]) {
                $insert->execute([$nombre, $prefijo]);
            }
        }

        $totalUsuarios = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        if ($totalUsuarios > 0) {
            return;
        }

        $ids = [];
        foreach ($pdo->query('SELECT id, prefijo FROM especialidades')->fetchAll() as $row) {
            $ids[$row['prefijo']] = (int) $row['id'];
        }

        $creado = date('Y-m-d H:i:s');
        $insert = $pdo->prepare(
            'INSERT INTO usuarios (nombre, email, password_hash, rol, activo, especialidad_id, consultorio, creado_en)
             VALUES (?, ?, ?, ?, 1, ?, ?, ?)'
        );

        $insert->execute([
            'Administrador del sistema',
            'admin@consultorio.local',
            password_hash('Admin123!', PASSWORD_DEFAULT),
            'admin',
            null,
            null,
            $creado,
        ]);

        $insert->execute([
            'Recepción',
            'recepcion@consultorio.local',
            password_hash('Recepcion123!', PASSWORD_DEFAULT),
            'recepcion',
            null,
            null,
            $creado,
        ]);

        $medicos = [
            ['Dra. Ana Torres', 'mg@consultorio.local', 'MG', 'Consultorio 1'],
            ['Dr. Héctor Salazar', 'mg2@consultorio.local', 'MG', 'Consultorio 6'],
            ['Dr. Luis Ramos', 'pediatria@consultorio.local', 'PEDI', 'Consultorio 2'],
            ['Dra. Sofía Vega', 'pediatria2@consultorio.local', 'PEDI', 'Consultorio 7'],
            ['Dra. Carla Méndez', 'mi@consultorio.local', 'MI', 'Consultorio 3'],
            ['Dr. Andrés Paredes', 'mi2@consultorio.local', 'MI', 'Consultorio 8'],
            ['Dr. José Herrera', 'odontologia@consultorio.local', 'OD', 'Consultorio 4'],
            ['Dra. Lucía Núñez', 'odontologia2@consultorio.local', 'OD', 'Consultorio 9'],
            ['Lic. María Flores', 'psicologia@consultorio.local', 'PSI', 'Consultorio 5'],
            ['Lic. Diego Castro', 'psicologia2@consultorio.local', 'PSI', 'Consultorio 10'],
        ];

        foreach ($medicos as [$nombre, $email, $prefijo, $consultorio]) {
            $insert->execute([
                $nombre,
                $email,
                password_hash('Medico123!', PASSWORD_DEFAULT),
                'medico',
                $ids[$prefijo] ?? null,
                $consultorio,
                $creado,
            ]);
        }
    }
}
