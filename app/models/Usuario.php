<?php

declare(strict_types=1);

abstract class Usuario
{
    protected int $id;
    protected string $nombre;
    protected string $email;
    protected string $passwordHash;
    protected string $rol;
    protected bool $activo;
    protected ?int $especialidadId;
    protected ?string $consultorio;
    protected string $creadoEn;

    public function __construct(array $row)
    {
        $this->id = (int) $row['id'];
        $this->nombre = (string) $row['nombre'];
        $this->email = (string) $row['email'];
        $this->passwordHash = (string) $row['password_hash'];
        $this->rol = (string) $row['rol'];
        $this->activo = (bool) $row['activo'];
        $this->especialidadId = isset($row['especialidad_id']) ? (int) $row['especialidad_id'] : null;
        $this->consultorio = $row['consultorio'] !== null ? (string) $row['consultorio'] : null;
        $this->creadoEn = (string) ($row['creado_en'] ?? '');
    }

    public static function fromRow(array $row): self
    {
        return match ($row['rol'] ?? '') {
            'admin' => new Administrador($row),
            'recepcion' => new Recepcionista($row),
            'medico' => new Medico($row),
            default => throw new InvalidArgumentException('Rol de usuario desconocido.'),
        };
    }

    public static function findById(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function findByEmail(string $email): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM usuarios WHERE lower(email) = lower(?)');
        $stmt->execute([trim($email)]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function all(): array
    {
        $rows = Database::connection()
            ->query('SELECT * FROM usuarios ORDER BY rol, nombre')
            ->fetchAll();

        return array_map(static fn (array $row): self => self::fromRow($row), $rows);
    }

    public static function medicos(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM usuarios WHERE rol = 'medico' ORDER BY nombre"
        );
        $stmt->execute();

        return array_map(static fn (array $row): self => self::fromRow($row), $stmt->fetchAll());
    }

    public static function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE lower(email) = lower(?)';
        $params = [$email];

        if ($excludeId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function crear(array $data, string $password): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO usuarios
                (nombre, email, password_hash, rol, activo, especialidad_id, consultorio, creado_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['nombre'],
            $data['email'],
            password_hash($password, PASSWORD_DEFAULT),
            $data['rol'],
            $data['activo'] ? 1 : 0,
            $data['especialidad_id'],
            $data['consultorio'],
            date('Y-m-d H:i:s'),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function actualizar(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE usuarios
             SET nombre = ?, email = ?, rol = ?, activo = ?, especialidad_id = ?, consultorio = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['nombre'],
            $data['email'],
            $data['rol'],
            $data['activo'] ? 1 : 0,
            $data['especialidad_id'],
            $data['consultorio'],
            $id,
        ]);
    }

    public static function actualizarPassword(int $id, string $password): void
    {
        $stmt = Database::connection()->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public static function cambiarEstado(int $id, bool $activo): void
    {
        $stmt = Database::connection()->prepare('UPDATE usuarios SET activo = ? WHERE id = ?');
        $stmt->execute([$activo ? 1 : 0, $id]);
    }

    public static function contarAdminsActivos(?int $excludeId = null): int
    {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE rol = 'admin' AND activo = 1";
        $params = [];

        if ($excludeId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRol(): string
    {
        return $this->rol;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function getEspecialidadId(): ?int
    {
        return $this->especialidadId;
    }

    public function getConsultorio(): ?string
    {
        return $this->consultorio;
    }

    public function getCreadoEn(): string
    {
        return $this->creadoEn;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'rol' => $this->rol,
            'activo' => $this->activo,
            'especialidad_id' => $this->especialidadId,
            'consultorio' => $this->consultorio,
            'creado_en' => $this->creadoEn,
        ];
    }

    abstract public function puede(string $accion): bool;
}
