<?php require __DIR__ . '/layout/header.php'; ?>

<div class="page-head">
    <div>
        <h1>Recepción</h1>
        <p class="muted">Emisión de turnos presenciales y control de la cola de espera.</p>
    </div>
    <span class="live-pill"><span class="dot"></span> En vivo</span>
</div>

<div class="grid grid-recepcion">
    <section class="card">
        <h2>Emitir turno</h2>

        <form id="formTurno" class="stack" novalidate>
            <label>
                Especialidad
                <select name="especialidad_id" id="especialidad" required>
                    <option value="">Seleccione una especialidad</option>
                    <?php foreach ($especialidades as $especialidad): ?>
                        <option value="<?= e($especialidad->getId()) ?>">
                            <?= e($especialidad->getNombre()) ?> (<?= e($especialidad->getPrefijo()) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                <span>Paciente <span class="muted">(opcional)</span></span>
                <input type="text" name="paciente" id="paciente" maxlength="120"
                       placeholder="Nombre del paciente">
            </label>

            <button type="submit" class="btn btn-primary btn-block">Generar turno</button>
        </form>

        <div id="ticket" class="ticket" hidden>
            <span class="ticket-label">Turno generado</span>
            <strong id="ticketCodigo" class="ticket-code">--</strong>
            <span id="ticketPaciente" class="muted"></span>
            <span id="ticketEspecialidad" class="ticket-specialty"></span>
            <span id="ticketHora" class="ticket-time"></span>
            <div class="ticket-actions">
                <button type="button" id="btnImprimir" class="btn btn-ghost btn-sm">Imprimir ticket</button>
                <button type="button" id="btnNuevo" class="btn btn-ghost btn-sm">Emitir otro</button>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card-head">
            <div>
                <h2>Cola de espera</h2>
                <p class="muted small">Orden FIFO: se atiende al más antiguo primero.</p>
            </div>
            <button type="button" id="btnRefreshCola" class="btn btn-ghost btn-sm">Actualizar</button>
        </div>

        <div id="colaEspera" class="queue-list">
            <?php $turnos = $colaInicial ?? []; require __DIR__ . '/partials/queue_list.php'; ?>
        </div>
    </section>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
