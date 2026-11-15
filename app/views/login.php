<?php require __DIR__ . '/layout/header.php'; ?>

<section class="login-wrap">
    <div class="login-card">
        <div class="login-brand">
            <span class="brand-mark large">+</span>
            <div>
                <h1>Sistema de Turnos</h1>
                <p class="muted">Gestión de consultorios y sala de espera</p>
            </div>
        </div>

        <form id="loginForm" class="stack" autocomplete="on" novalidate>
            <label>
                Correo electrónico
                <input type="email" name="email" id="email" autocomplete="username" required
                       placeholder="usuario@consultorio.local">
            </label>

            <label>
                Contraseña
                <input type="password" name="password" id="password" autocomplete="current-password" required
                       placeholder="••••••••">
            </label>

            <div id="loginError" class="alert alert-error" hidden></div>

            <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
        </form>

        <div class="demo-box">
            <p class="demo-title">Cuentas de demostración</p>
            <ul class="demo-list">
                <li><span>Administrador</span><code>admin@consultorio.local / Admin123!</code></li>
                <li><span>Recepción</span><code>recepcion@consultorio.local / Recepcion123!</code></li>
            </ul>

            <details class="demo-doctors">
                <summary>Médicos de demostración (10) · contraseña Medico123!</summary>
                <div class="demo-specialties">
                    <div class="demo-specialty">
                        <span class="demo-specialty-name">Medicina general</span>
                        <code>mg@consultorio.local</code>
                        <code>mg2@consultorio.local</code>
                    </div>
                    <div class="demo-specialty">
                        <span class="demo-specialty-name">Pediatría</span>
                        <code>pediatria@consultorio.local</code>
                        <code>pediatria2@consultorio.local</code>
                    </div>
                    <div class="demo-specialty">
                        <span class="demo-specialty-name">Medicina interna</span>
                        <code>mi@consultorio.local</code>
                        <code>mi2@consultorio.local</code>
                    </div>
                    <div class="demo-specialty">
                        <span class="demo-specialty-name">Odontología</span>
                        <code>odontologia@consultorio.local</code>
                        <code>odontologia2@consultorio.local</code>
                    </div>
                    <div class="demo-specialty">
                        <span class="demo-specialty-name">Psicología</span>
                        <code>psicologia@consultorio.local</code>
                        <code>psicologia2@consultorio.local</code>
                    </div>
                </div>
            </details>

            <p class="demo-hint">Para usar dos roles a la vez, abra una ventana privada o de incógnito.</p>
            <a class="demo-link" href="?action=pantalla" target="_blank" rel="noopener">Abrir pantalla de sala de espera</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/layout/footer.php'; ?>
