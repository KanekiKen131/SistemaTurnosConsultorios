<?php

declare(strict_types=1);

require_once __DIR__ . '/Usuario.php';

class Administrador extends Usuario
{
    public function puede(string $accion): bool
    {
        return true;
    }
}
