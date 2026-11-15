<?php

declare(strict_types=1);

class PantallaController
{
    public function index(): void
    {
        $gestor = new GestorColaTurnos();
        $estadoInicial = $gestor->salaEspera();
        $page = 'tv';
        $title = 'Sala de espera';

        require __DIR__ . '/../views/pantalla_tv.php';
    }

    public function apiTv(): void
    {
        $gestor = new GestorColaTurnos();
        $estado = $gestor->salaEspera();

        Response::ok($estado);
    }
}
