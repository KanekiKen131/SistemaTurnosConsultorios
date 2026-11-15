<?php

declare(strict_types=1);

class AuthService
{
    private static ?Usuario $cachedUser = null;
    private static bool $loaded = false;

    public static function boot(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('turnos_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }

    public static function attempt(string $email, string $password): ?Usuario
    {
        $usuario = Usuario::findByEmail($email);

        if (!$usuario || !$usuario->isActivo() || !password_verify($password, $usuario->getPasswordHash())) {
            return null;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $usuario->getId();
        Csrf::rotate();

        self::$cachedUser = $usuario;
        self::$loaded = true;

        return $usuario;
    }

    public static function user(): ?Usuario
    {
        if (self::$loaded) {
            return self::$cachedUser;
        }

        self::$loaded = true;

        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            return self::$cachedUser = null;
        }

        $usuario = Usuario::findById((int) $id);
        if (!$usuario || !$usuario->isActivo()) {
            unset($_SESSION['user_id']);
            return self::$cachedUser = null;
        }

        return self::$cachedUser = $usuario;
    }

    public static function check(): bool
    {
        return self::user() instanceof Usuario;
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => (bool) $params['secure'],
                'httponly' => (bool) $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
        self::$cachedUser = null;
        self::$loaded = true;
    }

    public static function requireUser(bool $api = false): Usuario
    {
        $usuario = self::user();

        if (!$usuario) {
            if ($api) {
                Response::error('No autenticado. Inicie sesión nuevamente.', 401);
            }

            Response::redirect('?action=login');
        }

        return $usuario;
    }

    public static function requireRol(array $roles, bool $api = false): Usuario
    {
        $usuario = self::requireUser($api);

        if (!in_array($usuario->getRol(), $roles, true)) {
            if ($api) {
                Response::error('No tiene permisos para realizar esta acción.', 403);
            }

            Response::redirect('?action=' . self::homeAction($usuario));
        }

        return $usuario;
    }

    public static function homeAction(Usuario $usuario): string
    {
        return match ($usuario->getRol()) {
            'admin' => 'admin',
            'recepcion' => 'recepcion',
            'medico' => 'medico',
            default => 'login',
        };
    }
}
