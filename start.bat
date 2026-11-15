@echo off
chcp 65001 >nul
setlocal

cd /d "%~dp0"

if "%PORT%"=="" set "PORT=8000"

where php >nul 2>nul
if errorlevel 1 (
    echo.
    echo No se encontro PHP.
    echo Instale PHP 8.1 o superior y habilite las extensiones pdo_sqlite y sqlite3.
    echo.
    pause
    exit /b 1
)

echo Sistema de Turnos para Consultorios
echo Servidor:   http://localhost:%PORT%/
echo Pantalla TV: http://IP-DEL-SERVIDOR:%PORT%/?action=pantalla
echo Presione Ctrl+C para detener.
echo.

php -S 0.0.0.0:%PORT% -t public

pause
