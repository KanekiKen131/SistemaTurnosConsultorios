<?php
$user = AuthService::user();

$espNombre = '';
foreach ($especialidades as $especialidad) {
    if ($especialidad->getId() === $user->getEspecialidadId()) {
        $espNombre = $especialidad->getNombre();
        break;
    }
}
?>

<?php require __DIR__ . '/layout/header.php'; ?>

<div class="page-head">
    <div>
        <h1>Atención médica</h1>
        <p class="muted">Llame al siguiente paciente respetando el orden de llegada.</p>
    </div>
    <span class="live-pill"><span class="dot"></span> En vivo</span>
</div>

<div class="grid grid-medico">
    <section class="card current-card">
        <div class="card-head">
            <h2>Paciente en atención</h2>
            <span id="estadoPill" class="badge <?= $turnoActual ? 'badge-atendiendo' : 'badge-espera' ?>"><?= $turnoActual ? 'Atendiendo' : 'Sin turno' ?></span>
        </div>

        <div id="turnoActual" class="current-turn">
            <span class="current-code<?= $turnoActual ? '' : ' muted' ?>"><?= e($turnoActual ? $turnoActual->getCodigo() : '---') ?></span>
            <span class="current-patient<?= $turnoActual ? '' : ' muted' ?>"><?= e($turnoActual ? ($turnoActual->getPaciente() ?: 'Paciente sin nombre') : 'Sin paciente en atención') ?></span>
            <span class="current-meta"><?= e($turnoActual
                ? 'Llamado ' . date('H:i', strtotime((string) $turnoActual->getFechaLlamado()))
                : 'Presione «Llamar siguiente paciente»') ?></span>
        </div>

        <div id="medicoInfo" class="medico-info">
            <span><?= e($espNombre !== '' ? $espNombre : 'Sin especialidad') ?></span>
            <span class="muted">·</span>
            <span><?= e($user->getConsultorio() ?: 'Sin consultorio') ?></span>
        </div>


        <div class="action-row">
            <button type="button" id="btnLlamar" class="btn btn-primary">Llamar siguiente paciente</button>
            <button type="button" id="btnAusente" class="btn btn-danger" <?= $turnoActual ? '' : 'disabled' ?>>Paciente ausente</button>
            <button type="button" id="btnFinalizar" class="btn btn-ghost" <?= $turnoActual ? '' : 'disabled' ?>>Finalizar atención</button>
        </div>
    </section>

    <section class="card">
        <div class="card-head">
            <div>
                <h2>Próximos en espera</h2>
                <p class="muted small">Misma especialidad, del más antiguo al más reciente.</p>
            </div>
            <button type="button" id="btnRefreshMedico" class="btn btn-ghost btn-sm">Actualizar</button>
        </div>

        <div id="colaMedico" class="queue-list">
            <?php $turnos = $colaInicial ?? []; require __DIR__ . '/partials/queue_list.php'; ?>
        </div>
    </section>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
