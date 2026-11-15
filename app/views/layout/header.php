<?php
$user = $user ?? AuthService::user();
$page = $page ?? '';
$title = $title ?? 'Sistema de Turnos';
$rol = $user?->getRol() ?? '';
$currentAction = (string) ($_GET['action'] ?? '');
$especialidadMedico = null;

if ($rol === 'medico' && $user?->getEspecialidadId()) {
    $especialidadMedico = Especialidad::find((int) $user->getEspecialidadId());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title><?= e($title) ?> · Turnos Consultorios</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body
    data-page="<?= e($page) ?>"
    data-rol="<?= e($rol) ?>"
    data-user-id="<?= e($user?->getId() ?? '') ?>"
    data-especialidad-id="<?= e($user?->getEspecialidadId() ?? '') ?>"
>
<?php if ($user): ?>
    <header class="topbar">
        <a class="brand" href="?action=<?= e(AuthService::homeAction($user)) ?>">
            <span class="brand-mark">+</span>
            <span class="brand-text">Turnos <small>Consultorios</small></span>
        </a>

        <nav class="nav" aria-label="Navegación principal">
            <?php if ($rol === 'admin'): ?>
                <a href="?action=admin"<?= $currentAction === 'admin' ? ' class="active"' : '' ?>>Usuarios</a>
            <?php endif; ?>
            <?php if ($rol === 'recepcion'): ?>
                <a href="?action=recepcion"<?= $currentAction === 'recepcion' ? ' class="active"' : '' ?>>Recepción</a>
            <?php endif; ?>
            <?php if ($rol === 'medico'): ?>
                <a href="?action=medico"<?= $currentAction === 'medico' ? ' class="active"' : '' ?>>Atención médica</a>
            <?php endif; ?>
            <a href="?action=historial"<?= $currentAction === 'historial' ? ' class="active"' : '' ?>>Historial</a>
            <a href="?action=pantalla" target="_blank" rel="noopener">Pantalla TV</a>
        </nav>

        <div class="userbox">
            <span class="user-name"><?= e($user->getNombre()) ?></span>
            <?php if ($rol === 'medico'): ?>
                <span class="user-meta">
                    <?= e($especialidadMedico?->getNombre() ?? 'Sin especialidad') ?>
                    · <?= e($user->getConsultorio() ?: 'Sin consultorio') ?>
                </span>
            <?php endif; ?>
            <span class="badge role-<?= e($rol) ?>"><?= e($rol) ?></span>
            <button type="button" class="btn btn-ghost btn-sm" data-logout>Cerrar sesión</button>
        </div>
    </header>
<?php endif; ?>
<main class="container">
