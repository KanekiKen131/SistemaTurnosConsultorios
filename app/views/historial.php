<?php require __DIR__ . '/layout/header.php'; ?>

<div class="page-head">
    <div>
        <h1>Historial de turnos</h1>
        <p class="muted">Consulte los turnos emitidos, atendidos y ausentes por día.</p>
    </div>
    <button type="button" id="btnImprimirHistorial" class="btn btn-ghost">Imprimir</button>
</div>

<section class="card">
    <form id="historialFiltros" class="filters" novalidate>
        <label>
            Fecha
            <input type="date" name="dia" id="historialDia" value="<?= e(date('Y-m-d')) ?>" required>
        </label>

        <label>
            Especialidad
            <select name="especialidad_id" id="historialEspecialidad">
                <option value="">Todas</option>
                <?php foreach ($especialidades as $especialidad): ?>
                    <option value="<?= e($especialidad->getId()) ?>"><?= e($especialidad->getNombre()) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Estado
            <select name="estado" id="historialEstado">
                <option value="">Todos</option>
                <option value="ESPERA">En espera</option>
                <option value="ATENDIENDO">Atendiendo</option>
                <option value="ATENDIDO">Atendido</option>
                <option value="AUSENTE">Ausente</option>
            </select>
        </label>

        <button type="submit" class="btn btn-primary">Filtrar</button>
    </form>
</section>

<div class="summary-grid">
    <div class="summary-card"><span>Total</span><strong id="sumTotal">0</strong></div>
    <div class="summary-card state-espera"><span>En espera</span><strong id="sumEspera">0</strong></div>
    <div class="summary-card state-atendiendo"><span>Atendiendo</span><strong id="sumAtendiendo">0</strong></div>
    <div class="summary-card state-atendido"><span>Atendidos</span><strong id="sumAtendido">0</strong></div>
    <div class="summary-card state-ausente"><span>Ausentes</span><strong id="sumAusente">0</strong></div>
</div>

<section class="card">
    <div class="card-head">
        <h2 id="historialTitulo">Turnos del día</h2>
        <span class="muted small" id="historialContador"></span>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Paciente</th>
                    <th>Especialidad</th>
                    <th>Médico</th>
                    <th>Consultorio</th>
                    <th>Estado</th>
                    <th>Emitido</th>
                    <th>Llamado</th>
                    <th>Fin</th>
                </tr>
            </thead>
            <tbody id="tablaHistorial">
                <tr><td colspan="9" class="muted">Cargando turnos…</td></tr>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/layout/footer.php'; ?>
