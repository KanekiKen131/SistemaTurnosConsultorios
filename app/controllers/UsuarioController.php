<?php

declare(strict_types=1);

class UsuarioController
{
    public function indexAdmin(): void
    {
        AuthService::requireRol(['admin']);

        $especialidades = Especialidad::all();
        $usuariosIniciales = Usuario::all();
        $page = 'admin';
        $title = 'Administración de usuarios';

        require __DIR__ . '/../views/admin.php';
    }

    public function apiListar(): void
    {
        AuthService::requireRol(['admin'], true);

        $usuarios = array_map(
            static fn (Usuario $usuario): array => $usuario->toArray(),
            Usuario::all()
        );

        Response::ok(['usuarios' => $usuarios]);
    }

    public function apiGuardar(): void
    {
        $actual = AuthService::requireRol(['admin'], true);
        Csrf::validate();

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $rol = (string) ($_POST['rol'] ?? '');
        $activo = (int) ($_POST['activo'] ?? 1) === 1;
        $especialidadId = (int) ($_POST['especialidad_id'] ?? 0);
        $consultorio = trim((string) ($_POST['consultorio'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 80) {
            Response::error('El nombre debe tener entre 2 y 80 caracteres.', 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('El correo electrónico no es válido.', 422);
        }

        if (!in_array($rol, ['admin', 'recepcion', 'medico'], true)) {
            Response::error('El rol seleccionado no es válido.', 422);
        }

        if ($rol === 'medico') {
            if ($especialidadId <= 0 || !Especialidad::find($especialidadId)) {
                Response::error('Seleccione una especialidad válida para el médico.', 422);
            }

            if ($consultorio === '' || mb_strlen($consultorio) > 60) {
                Response::error('Indique el consultorio del médico (máximo 60 caracteres).', 422);
            }
        } else {
            $especialidadId = 0;
            $consultorio = '';
        }

        if (Usuario::emailExists($email, $id > 0 ? $id : null)) {
            Response::error('Ya existe un usuario con ese correo electrónico.', 422);
        }

        $existente = $id > 0 ? Usuario::findById($id) : null;

        if ($id > 0 && !$existente) {
            Response::error('El usuario que intenta editar no existe.', 404);
        }

        if ($existente && $existente->getId() === $actual->getId() && !$activo) {
            Response::error('No puede desactivar su propia cuenta.', 422);
        }

        if (
            $existente
            && $existente->getRol() === 'admin'
            && ($rol !== 'admin' || !$activo)
            && Usuario::contarAdminsActivos($existente->getId()) < 1
        ) {
            Response::error('Debe existir al menos un administrador activo.', 422);
        }

        if ($id === 0 && mb_strlen($password) < 8) {
            Response::error('La contraseña debe tener al menos 8 caracteres.', 422);
        }

        if ($id > 0 && $password !== '' && mb_strlen($password) < 8) {
            Response::error('La nueva contraseña debe tener al menos 8 caracteres.', 422);
        }

        $data = [
            'nombre' => $nombre,
            'email' => $email,
            'rol' => $rol,
            'activo' => $activo,
            'especialidad_id' => $especialidadId > 0 ? $especialidadId : null,
            'consultorio' => $consultorio !== '' ? $consultorio : null,
        ];

        if ($id > 0) {
            Usuario::actualizar($id, $data);

            if ($password !== '') {
                Usuario::actualizarPassword($id, $password);
            }

            Response::ok(['message' => 'Usuario actualizado correctamente.']);
        }

        Usuario::crear($data, $password);

        Response::ok(['message' => 'Usuario creado correctamente.']);
    }

    public function apiEstado(): void
    {
        $actual = AuthService::requireRol(['admin'], true);
        Csrf::validate();

        $id = (int) ($_POST['id'] ?? 0);
        $activo = (int) ($_POST['activo'] ?? 0) === 1;
        $usuario = Usuario::findById($id);

        if (!$usuario) {
            Response::error('Usuario no encontrado.', 404);
        }

        if ($usuario->getId() === $actual->getId() && !$activo) {
            Response::error('No puede desactivar su propia cuenta.', 422);
        }

        if (
            $usuario->getRol() === 'admin'
            && !$activo
            && Usuario::contarAdminsActivos($usuario->getId()) < 1
        ) {
            Response::error('Debe existir al menos un administrador activo.', 422);
        }

        Usuario::cambiarEstado($id, $activo);

        Response::ok([
            'message' => $activo ? 'Usuario activado.' : 'Usuario desactivado.',
        ]);
    }

    public function apiResetPassword(): void
    {
        AuthService::requireRol(['admin'], true);
        Csrf::validate();

        $id = (int) ($_POST['id'] ?? 0);
        $password = (string) ($_POST['password'] ?? '');

        if ($id <= 0 || !Usuario::findById($id)) {
            Response::error('Usuario no encontrado.', 404);
        }

        if (mb_strlen($password) < 8) {
            Response::error('La contraseña debe tener al menos 8 caracteres.', 422);
        }

        Usuario::actualizarPassword($id, $password);

        Response::ok(['message' => 'Contraseña actualizada correctamente.']);
    }
}
