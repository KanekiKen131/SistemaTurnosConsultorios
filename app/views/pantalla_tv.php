<?php
$atendiendo = $estadoInicial['atendiendo'] ?? [];
$proximos = $estadoInicial['proximos'] ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sala de espera · Turnos</title>
    <link rel="stylesheet" href="assets/css/tv.css">
</head>
<body class="tv-body">
    <header class="tv-header">
        <div class="tv-brand">
            <span class="tv-logo">+</span>
            <div>
                <h1>Sala de espera</h1>
                <p>Turnos Consultorios</p>
            </div>
        </div>

        <div class="tv-tools">
            <span id="tvConexion" class="tv-connection"><span class="dot"></span> Conectado</span>
            <button type="button" id="btnSonido" class="tv-btn">Activar sonido</button>
            <button type="button" id="btnPantalla" class="tv-btn">Pantalla completa</button>
            <time id="tvReloj" class="tv-clock">--:--</time>
        </div>
    </header>

    <main class="tv-main">
        <section class="tv-current">
            <div class="tv-section-head">
                <h2>Turno en atención</h2>
                <span id="tvActualizado" class="tv-updated">Actualizado <?= e(date('H:i:s', strtotime($estadoInicial['actualizado'] ?? 'now'))) ?></span>
            </div>
            <div id="tvAtendiendo" class="tv-cards<?= count($atendiendo) > 1 ? ' many' : '' ?>">
                <?php if (empty($atendiendo)): ?>
                    <div class="tv-empty">
                        <span class="tv-empty-code">---</span>
                        <p>Esperando llamado de pacientes</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($atendiendo as $turno): ?>
                        <article class="tv-card">
                            <div class="tv-card-top">
                                <span class="tv-specialty"><?= e($turno['especialidad']['nombre'] ?? 'Especialidad') ?></span>
                                <span class="tv-room"><?= e($turno['consultorio'] ?: ($turno['medico']['consultorio'] ?? '') ?: 'Consultorio') ?></span>
                            </div>
                            <div class="tv-code"><?= e($turno['codigo']) ?></div>
                            <div class="tv-patient"><?= e($turno['paciente'] ? 'Paciente: ' . $turno['paciente'] : 'Paciente sin nombre') ?></div>
                            <div class="tv-doctor"><?= e($turno['medico']['nombre'] ?? '' ? 'Atiende: ' . $turno['medico']['nombre'] : 'Médico por asignar') ?></div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <aside class="tv-next">
            <div class="tv-section-head">
                <h2>Próximos turnos</h2>
                <span class="tv-legend"><span class="dot gray"></span> En espera</span>
            </div>
            <ol id="tvProximos" class="tv-next-list">
                <?php if (empty($proximos)): ?>
                    <li class="tv-next-empty">Sin turnos en espera</li>
                <?php else: ?>
                    <?php foreach ($proximos as $turno): ?>
                        <li>
                            <span class="tv-next-code"><?= e($turno['codigo']) ?></span>
                            <span class="tv-next-specialty"><?= e($turno['especialidad']['nombre'] ?? 'Especialidad') ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ol>
        </aside>
    </main>

    <footer class="tv-footer">
        <span>Por favor, permanezca atento a su turno.</span>
        <span>Los turnos se muestran en orden de llegada.</span>
    </footer>

    <script src="assets/js/tv.js"></script>
</body>
</html>
