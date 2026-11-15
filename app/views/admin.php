<?php require __DIR__ . '/layout/header.php'; ?>

<div class="page-head">
    <div>
        <h1>Administración de usuarios</h1>
        <p class="muted">Cree, edite, active o desactive cuentas del personal del consultorio.</p>
    </div>
    <button type="button" id="btnNuevoUsuario" class="btn btn-primary">Nuevo usuario</button>
</div>

<div id="adminMsg" class="alert" hidden></div>

<?php
$espPorId = [];
foreach ($especialidades as $especialidad) {
    $espPorId[$especialidad->getId()] = $especialidad->getNombre();
}
$rolEtiquetas = ['admin' => 'Administrador', 'recepcion' => 'Recepción', 'medico' => 'Médico'];
?>

<section class="card">
    <div class="card-head">
        <h2>Cuentas registradas</h2>
        <button type="button" id="btnRefreshUsuarios" class="btn btn-ghost btn-sm">Actualizar</button>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Especialidad</th>
                    <th>Consultorio</th>
                    <th>Estado</th>
                    <th class="right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaUsuarios">
                <?php $usuarios = $usuariosIniciales ?? []; require __DIR__ . '/partials/admin_users_rows.php'; ?>
            </tbody>
        </table>
    </div>
</section>

<dialog id="userDialog" class="dialog">
    <form id="userForm" class="stack" novalidate>
        <div class="dialog-head">
            <h2 id="userDialogTitle">Nuevo usuario</h2>
            <button type="button" class="btn btn-ghost btn-sm" data-close-dialog>Cerrar</button>
        </div>

        <input type="hidden" name="id" id="usuarioId" value="0">

        <label>
            Nombre completo
            <input type="text" name="nombre" id="usuarioNombre" maxlength="80" required>
        </label>

        <label>
            Correo electrónico
            <input type="email" name="email" id="usuarioEmail" required>
        </label>

        <label>
            Rol
            <select name="rol" id="usuarioRol" required>
                <option value="recepcion">Recepcionista</option>
                <option value="medico">Médico / Especialista</option>
                <option value="admin">Administrador</option>
            </select>
        </label>

        <div id="fieldEspecialidad" class="field-medico">
            <label>
                Especialidad
                <select name="especialidad_id" id="usuarioEspecialidad">
                    <option value="0">Seleccione una especialidad</option>
                    <?php foreach ($especialidades as $especialidad): ?>
                        <option value="<?= e($especialidad->getId()) ?>">
                            <?= e($especialidad->getNombre()) ?> (<?= e($especialidad->getPrefijo()) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Consultorio
                <input type="text" name="consultorio" id="usuarioConsultorio" maxlength="60"
                       placeholder="Ej. Consultorio 1">
            </label>
        </div>

        <label>
            <span id="passwordLabel">Contraseña</span>
            <input type="password" name="password" id="usuarioPassword" minlength="8" autocomplete="new-password">
            <span id="passwordHelp" class="muted small">Mínimo 8 caracteres.</span>
        </label>

        <label class="check-row">
            <input type="checkbox" name="activo" id="usuarioActivo" value="1" checked>
            Cuenta activa
        </label>

        <div id="userFormError" class="alert alert-error" hidden></div>

        <div class="dialog-actions">
            <button type="button" class="btn btn-ghost" data-close-dialog>Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar usuario</button>
        </div>
    </form>
</dialog>

<?php require __DIR__ . '/layout/footer.php'; ?>
