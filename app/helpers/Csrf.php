<?php

declare(strict_types=1);

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf'];
    }

    public static function rotate(): string
    {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));

        return (string) $_SESSION['_csrf'];
    }

    public static function validate(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';

        if (!is_string($token) || $token === '' || !hash_equals(self::token(), $token)) {
            Response::error('Token de seguridad inválido o expirado. Recargue la página.', 419);
        }
    }
}
