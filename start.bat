@echo off
chcp 65001 >nul
setlocal
title Sistema de Turnos - Servidor

cd /d "%~dp0"

if "%PORT%"=="" set "PORT=8000"

set "BUNDLED_PHP=%~dp0runtime\php\php.exe"

if not exist "%BUNDLED_PHP%" goto system_php

:bundled_php
set "PHP_DIR=%~dp0runtime\php"
set "PHPRC=%PHP_DIR%"
set "PHP_EXE=%BUNDLED_PHP%"

if not exist "%SystemRoot%\System32\VCRUNTIME140.dll" goto install_vc
if not exist "%SystemRoot%\System32\ucrtbase.dll" goto install_vc
goto check_bundled

:install_vc
if defined VC_TRIED goto php_failed
set "VC_TRIED=1"
if not exist "%~dp0runtime\vc_redist.x64.exe" goto php_failed
echo.
echo Preparando un componente necesario de Windows...
"%~dp0runtime\vc_redist.x64.exe" /install /quiet /norestart

:check_bundled
"%PHP_EXE%" -v >nul 2>nul
if not errorlevel 1 goto run_bundled
if defined VC_TRIED goto php_failed
goto install_vc

:php_failed
echo.
echo No se pudo iniciar PHP.
echo Instale Microsoft Visual C++ Redistributable x64 y vuelva a intentar.
echo.
pause
exit /b 1

:run_bundled
echo Sistema de Turnos para Consultorios
echo Servidor:    http://localhost:%PORT%/
echo Pantalla TV: http://IP-DEL-SERVIDOR:%PORT%/?action=pantalla
echo Presione Ctrl+C o cierre esta ventana para detener.
echo.
start "" /b powershell -NoProfile -WindowStyle Hidden -Command "Start-Sleep -Seconds 2; Start-Process 'http://localhost:%PORT%/'"
"%PHP_EXE%" -c "%PHP_DIR%\php.ini" -d extension_dir="%PHP_DIR%\ext" -d extension=pdo_sqlite -d extension=sqlite3 -d extension=mbstring -S 0.0.0.0:%PORT% -t public
goto end

:system_php
where php >nul 2>nul
if errorlevel 1 (
    echo.
    echo No se encontro PHP.
    echo Use el paquete portable de Windows o instale PHP 8.1 o superior.
    echo.
    pause
    exit /b 1
)
set "PHP_EXE=php"
"%PHP_EXE%" -v >nul 2>nul
if errorlevel 1 (
    echo.
    echo PHP esta instalado pero no se pudo iniciar.
    echo.
    pause
    exit /b 1
)

echo Sistema de Turnos para Consultorios
echo Servidor:    http://localhost:%PORT%/
echo Pantalla TV: http://IP-DEL-SERVIDOR:%PORT%/?action=pantalla
echo Presione Ctrl+C o cierre esta ventana para detener.
echo.
start "" /b powershell -NoProfile -WindowStyle Hidden -Command "Start-Sleep -Seconds 2; Start-Process 'http://localhost:%PORT%/'"
"%PHP_EXE%" -S 0.0.0.0:%PORT% -t public

:end
pause
