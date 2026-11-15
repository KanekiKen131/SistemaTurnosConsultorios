<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../config/database.php';

spl_autoload_register(static function (string $class): void {
    $directories = ['models', 'services', 'controllers', 'helpers'];

    foreach ($directories as $directory) {
        $file = __DIR__ . '/../app/' . $directory . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

AuthService::boot();

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: SAMEORIGIN');
header(
    "Content-Security-Policy: default-src 'self'; " .
    "img-src 'self' data:; " .
    "style-src 'self' 'unsafe-inline'; " .
    "script-src 'self'; " .
    "connect-src 'self'; " .
    "base-uri 'self'; " .
    "form-action 'self'"
);

$action = (string) ($_GET['action'] ?? 'home');
$isApi = str_starts_with($action, 'api_');

$renderErrorPage = static function (int $status, string $message): never {
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Error ' . $status . '</title>'
        . '<link rel="stylesheet" href="assets/css/app.css"></head><body>'
        . '<main class="container"><section class="card error-page">'
        . '<h1>Error ' . $status . '</h1><p>' . e($message) . '</p>'
        . '<a class="btn btn-primary" href="?action=login">Volver al inicio</a>'
        . '</section></main></body></html>';
    exit;
};

try {
    switch ($action) {
        case 'home':
            $usuario = AuthService::user();
            Response::redirect($usuario ? '?action=' . AuthService::homeAction($usuario) : '?action=login');
            break;

        case 'login':
            (new AuthController())->showLogin();
            break;

        case 'api_login':
            (new AuthController())->login();
            break;

        case 'api_logout':
            (new AuthController())->logout();
            break;

        case 'api_me':
            (new AuthController())->me();
            break;

        case 'recepcion':
            (new TurnoController())->indexRecepcion();
            break;

        case 'medico':
            (new TurnoController())->indexMedico();
            break;

        case 'admin':
            (new UsuarioController())->indexAdmin();
            break;

        case 'historial':
            (new TurnoController())->indexHistorial();
            break;

        case 'pantalla':
            (new PantallaController())->index();
            break;

        case 'api_especialidades':
            (new TurnoController())->apiEspecialidades();
            break;

        case 'api_medicos':
            (new TurnoController())->apiMedicos();
            break;

        case 'api_crear_turno':
            (new TurnoController())->apiCrearTurno();
            break;

        case 'api_cola':
            (new TurnoController())->apiCola();
            break;

        case 'api_turno_actual':
            (new TurnoController())->apiTurnoActual();
            break;

        case 'api_llamar_siguiente':
            (new TurnoController())->apiLlamarSiguiente();
            break;

        case 'api_marcar_ausente':
            (new TurnoController())->apiMarcarAusente();
            break;

        case 'api_finalizar':
            (new TurnoController())->apiFinalizar();
            break;

        case 'api_historial':
            (new TurnoController())->apiHistorial();
            break;

        case 'api_tv':
            (new PantallaController())->apiTv();
            break;

        case 'api_usuarios':
            (new UsuarioController())->apiListar();
            break;

        case 'api_usuario_guardar':
            (new UsuarioController())->apiGuardar();
            break;

        case 'api_usuario_estado':
            (new UsuarioController())->apiEstado();
            break;

        case 'api_usuario_password':
            (new UsuarioController())->apiResetPassword();
            break;

        default:
            if ($isApi) {
                Response::error('Acción no encontrada.', 404);
            }

            $renderErrorPage(404, 'La página solicitada no existe.');
    }
} catch (InvalidArgumentException $e) {
    if ($isApi) {
        Response::error($e->getMessage(), 422);
    }

    $renderErrorPage(422, $e->getMessage());
} catch (PDOException $e) {
    error_log('[turnos db] ' . $e->getMessage());

    if ($isApi) {
        Response::error('Error de base de datos. Intente nuevamente.', 500);
    }

    $renderErrorPage(500, 'No se pudo acceder a la base de datos.');
} catch (RuntimeException $e) {
    if ($isApi) {
        Response::error($e->getMessage(), 422);
    }

    $renderErrorPage(500, $e->getMessage());
} catch (Throwable $e) {
    error_log('[turnos] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());

    if ($isApi) {
        Response::error('Error interno del servidor.', 500);
    }

    $renderErrorPage(500, 'Ocurrió un error inesperado. Intente nuevamente.');
}
