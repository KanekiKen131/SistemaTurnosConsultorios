<?php

declare(strict_types=1);

class AuthController
{
    public function showLogin(): void
    {
        if (AuthService::check()) {
            Response::redirect('?action=' . AuthService::homeAction(AuthService::user()));
        }

        $page = 'login';
        $title = 'Iniciar sesión';
        require __DIR__ . '/../views/login.php';
    }

    public function login(): void
    {
        Csrf::validate();

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            Response::error('Ingrese su correo y contraseña.', 422);
        }

        $usuario = AuthService::attempt($email, $password);

        if (!$usuario) {
            Response::error('Credenciales inválidas o cuenta inactiva.', 401);
        }

        Response::ok([
            'usuario' => $usuario->toArray(),
            'redirect' => '?action=' . AuthService::homeAction($usuario),
        ]);
    }

    public function logout(): void
    {
        Csrf::validate();
        AuthService::logout();

        Response::ok(['redirect' => '?action=login']);
    }

    public function me(): void
    {
        $usuario = AuthService::requireUser(true);

        Response::ok(['usuario' => $usuario->toArray()]);
    }
}
