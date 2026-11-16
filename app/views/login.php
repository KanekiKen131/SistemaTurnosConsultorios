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
                       placeholder="correo@ejemplo.com">
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
            <p class="demo-hint">Para usar dos roles a la vez, abra una ventana privada o de incógnito.</p>
            <a class="demo-link" href="?action=pantalla" target="_blank" rel="noopener">Abrir pantalla de sala de espera</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/layout/footer.php'; ?>
