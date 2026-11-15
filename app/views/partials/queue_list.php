<?php if (empty($turnos)): ?>
    <p class="empty-state">No hay turnos en espera por ahora.</p>
<?php else: ?>
    <?php foreach ($turnos as $turno): ?>
        <div class="queue-item state-<?= e(strtolower($turno->getEstado())) ?>">
            <span class="queue-code"><?= e($turno->getCodigo()) ?></span>
            <div class="queue-main">
                <strong><?= e($turno->getPaciente() ?: 'Sin nombre registrado') ?></strong>
                <span><?= e($turno->getEspecialidadNombre() ?: 'Especialidad') ?> · Emitido <?= e(date('H:i', strtotime($turno->getFechaCreacion()))) ?></span>
            </div>
            <span class="queue-time"><?= e(date('H:i', strtotime($turno->getFechaCreacion()))) ?></span>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
