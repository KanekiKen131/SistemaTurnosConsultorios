<?php

declare(strict_types=1);

class Especialidad
{
    private int $id;
    private string $nombre;
    private string $prefijo;

    public function __construct(array $row)
    {
        $this->id = (int) $row['id'];
        $this->nombre = (string) $row['nombre'];
        $this->prefijo = (string) $row['prefijo'];
    }

    public static function all(): array
    {
        $rows = Database::connection()
            ->query('SELECT * FROM especialidades ORDER BY id')
            ->fetchAll();

        return array_map(static fn (array $row): self => new self($row), $rows);
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM especialidades WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? new self($row) : null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getPrefijo(): string
    {
        return $this->prefijo;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'prefijo' => $this->prefijo,
        ];
    }
}
