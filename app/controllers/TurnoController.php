<?php

declare(strict_types=1);

class TurnoController
{
    private GestorColaTurnos $gestor;

    public function __construct()
    {
        $this->gestor = new GestorColaTurnos();
    }

    public function indexRecepcion(): void
    {
        AuthService::requireRol(['recepcion']);

        $especialidades = Especialidad::all();
        $colaInicial = $this->gestor->colaDeEspecialidad();
        $page = 'recepcion';
        $title = 'Recepción';

        require __DIR__ . '/../views/recepcion.php';
    }

    public function indexMedico(): void
    {
        $usuario = AuthService::requireRol(['medico']);

        $especialidades = Especialidad::all();
        $turnoActual = $this->gestor->turnoActualDeMedico($usuario->getId());
        $colaInicial = $this->gestor->colaDeEspecialidad($usuario->getEspecialidadId());
        $page = 'medico';
        $title = 'Atención médica';

        require __DIR__ . '/../views/medico.php';
    }

    public function indexHistorial(): void
    {
        AuthService::requireUser();

        $especialidades = Especialidad::all();
        $page = 'historial';
        $title = 'Historial de turnos';

        require __DIR__ . '/../views/historial.php';
    }

    public function apiEspecialidades(): void
    {
        AuthService::requireUser(true);

        $especialidades = array_map(
            static fn (Especialidad $especialidad): array => $especialidad->toArray(),
            Especialidad::all()
        );

        Response::ok(['especialidades' => $especialidades]);
    }

    public function apiMedicos(): void
    {
        AuthService::requireRol(['admin', 'recepcion'], true);

        $medicos = array_map(
            static fn (Usuario $medico): array => $medico->toArray(),
            Usuario::medicos()
        );

        Response::ok(['medicos' => $medicos]);
    }

    public function apiCrearTurno(): void
    {
        AuthService::requireRol(['recepcion'], true);
        Csrf::validate();

        $especialidadId = (int) ($_POST['especialidad_id'] ?? 0);
        $paciente = trim((string) ($_POST['paciente'] ?? ''));

        if ($especialidadId <= 0) {
            Response::error('Seleccione una especialidad.', 422);
        }

        $turno = $this->gestor->emitirTurno($especialidadId, $paciente !== '' ? $paciente : null);

        Response::ok(['turno' => $turno->toArray()]);
    }

    public function apiCola(): void
    {
        $usuario = AuthService::requireRol(['recepcion', 'medico'], true);

        $especialidadId = null;

        if ($usuario->getRol() === 'medico') {
            $especialidadId = $usuario->getEspecialidadId();
        } elseif (isset($_GET['medico_id']) && (int) $_GET['medico_id'] > 0) {
            $medico = Usuario::findById((int) $_GET['medico_id']);
            $especialidadId = $medico?->getEspecialidadId();
        } elseif (isset($_GET['especialidad_id']) && (int) $_GET['especialidad_id'] > 0) {
            $especialidadId = (int) $_GET['especialidad_id'];
        }

        $cola = array_map(
            static fn (Turno $turno): array => $turno->toArray(),
            $this->gestor->colaDeEspecialidad($especialidadId)
        );

        Response::ok([
            'cola' => $cola,
            'conteo' => count($cola),
        ]);
    }

    public function apiTurnoActual(): void
    {
        AuthService::requireRol(['medico'], true);

        $turno = $this->gestor->turnoActualDeMedico(AuthService::requireUser(true)->getId());

        Response::ok(['turno' => $turno?->toArray()]);
    }

    public function apiLlamarSiguiente(): void
    {
        AuthService::requireRol(['medico'], true);
        Csrf::validate();

        $turno = $this->gestor->llamarSiguiente(AuthService::requireUser(true)->getId());

        Response::ok([
            'turno' => $turno?->toArray(),
            'message' => $turno ? 'Paciente llamado.' : 'No hay pacientes en espera para esta especialidad.',
        ]);
    }

    public function apiMarcarAusente(): void
    {
        AuthService::requireRol(['medico'], true);
        Csrf::validate();

        $medicoId = AuthService::requireUser(true)->getId();
        $turnoId = (int) ($_POST['turno_id'] ?? 0);

        if ($turnoId <= 0) {
            Response::error('Turno no válido.', 422);
        }

        $resultado = $this->gestor->marcarAusente($turnoId, $medicoId);

        Response::ok([
            'ausente' => $resultado['ausente']?->toArray(),
            'turno' => $resultado['siguiente']?->toArray(),
            'message' => $resultado['siguiente']
                ? 'Paciente marcado como ausente. Se llamó al siguiente.'
                : 'Paciente marcado como ausente. No hay más turnos en espera.',
        ]);
    }

    public function apiFinalizar(): void
    {
        AuthService::requireRol(['medico'], true);
        Csrf::validate();

        $medicoId = AuthService::requireUser(true)->getId();
        $turnoId = (int) ($_POST['turno_id'] ?? 0);

        if ($turnoId <= 0) {
            Response::error('Turno no válido.', 422);
        }

        $turno = $this->gestor->finalizar($turnoId, $medicoId);

        Response::ok([
            'turno' => $turno->toArray(),
            'message' => 'Atención finalizada.',
        ]);
    }

    public function apiHistorial(): void
    {
        AuthService::requireUser(true);

        $dia = (string) ($_GET['dia'] ?? date('Y-m-d'));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia)) {
            Response::error('Fecha no válida.', 422);
        }

        $especialidadId = isset($_GET['especialidad_id']) && (int) $_GET['especialidad_id'] > 0
            ? (int) $_GET['especialidad_id']
            : null;

        $estado = isset($_GET['estado']) && $_GET['estado'] !== ''
            ? strtoupper((string) $_GET['estado'])
            : null;

        $turnos = array_map(
            static fn (Turno $turno): array => $turno->toArray(),
            $this->gestor->historial($dia, $especialidadId, $estado)
        );

        Response::ok([
            'dia' => $dia,
            'turnos' => $turnos,
            'resumen' => $this->gestor->resumenDia($dia),
        ]);
    }

}
