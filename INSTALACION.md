# Instalación y uso

Sistema de Turnos para Consultorios en PHP con SQLite. No requiere Composer, Apache ni MySQL.

## Contenido del paquete

```
app/               Código de la aplicación (modelos, vistas, controladores y servicios)
config/            Conexión y creación automática de la base de datos
public/            Punto de entrada, estilos y JavaScript
start.sh           Inicio en Linux / macOS
start.bat          Inicio en Windows
package.sh         Genera el ZIP instalable
README.md          Documentación general
INSTALACION.md     Esta guía
```

La base de datos `database.sqlite` se crea automáticamente en el primer acceso.

## Requisitos

- PHP 8.1 o superior.
- Extensiones `pdo_sqlite` y `sqlite3`.
- Un navegador moderno.

Comprobar las extensiones:

```bash
php -m | grep -E "pdo_sqlite|sqlite3"
```

En Windows, habilitar estas extensiones en `php.ini` quitando el punto y coma de:

```ini
extension=pdo_sqlite
extension=sqlite3
```

## Instalación en Windows

1. Instalar PHP 8.1 o superior y agregar la carpeta de PHP al `PATH`.
2. Descomprimir el ZIP en una carpeta, por ejemplo `C:\SistemaTurnosConsultorios`.
3. Ejecutar `start.bat`.
4. Abrir `http://localhost:8000/`.

Si Windows muestra una advertencia de seguridad, permitir la ejecución del archivo.

## Instalación en Linux / macOS

```bash
cd SistemaTurnosConsultorios
chmod +x start.sh
./start.sh
```

También se puede iniciar sin permisos de ejecución:

```bash
bash start.sh
```

Abrir `http://localhost:8000/`.

## Uso desde otro equipo o televisor

1. Conectar el servidor y el televisor a la misma red.
2. Obtener la IP del servidor:
   - Linux: `hostname -I`
   - Windows: `ipconfig`
3. Permitir el puerto `8000` en el firewall del servidor.
4. Abrir en el televisor:

```
http://IP_DEL_SERVIDOR:8000/?action=pantalla
```

5. Presionar **Pantalla completa** y **Activar sonido** una vez en el navegador del televisor.

La pantalla se actualiza cada 2 segundos y avisa con sonido y destello cuando se llama un turno nuevo.

## Cuentas iniciales

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@consultorio.local` | `Admin123!` |
| Recepción | `recepcion@consultorio.local` | `Recepcion123!` |
| Médico · Medicina general | `mg@consultorio.local` | `Medico123!` |
| Médico · Medicina general | `mg2@consultorio.local` | `Medico123!` |
| Médico · Pediatría | `pediatria@consultorio.local` | `Medico123!` |
| Médico · Pediatría | `pediatria2@consultorio.local` | `Medico123!` |
| Médico · Medicina interna | `mi@consultorio.local` | `Medico123!` |
| Médico · Medicina interna | `mi2@consultorio.local` | `Medico123!` |
| Médico · Odontología | `odontologia@consultorio.local` | `Medico123!` |
| Médico · Odontología | `odontologia2@consultorio.local` | `Medico123!` |
| Médico · Psicología | `psicologia@consultorio.local` | `Medico123!` |
| Médico · Psicología | `psicologia2@consultorio.local` | `Medico123!` |

Cambiar las contraseñas desde el módulo de administración después de la primera instalación.

## Usar dos roles al mismo tiempo

La sesión se guarda por navegador. Para tener recepción y médico abiertos a la vez:

1. Abrir el sistema en una ventana normal e iniciar sesión con el primer rol.
2. Abrir una ventana privada o de incógnito e iniciar sesión con el segundo rol.
3. La pantalla de TV puede quedar abierta sin iniciar sesión.

También se pueden usar dos navegadores o dos perfiles distintos. Iniciar sesión en la misma ventana normal reemplaza la sesión anterior.

## Cambiar el puerto

Linux / macOS:

```bash
PORT=8001 ./start.sh
```

Windows:

```bat
set PORT=8001
start.bat
```

Luego abrir `http://localhost:8001/`.

## Reiniciar la base de datos

Detener el servidor y eliminar:

```bash
rm -f database.sqlite database.sqlite-shm database.sqlite-wal
```

En Windows, eliminar `database.sqlite`, `database.sqlite-shm` y `database.sqlite-wal` de la carpeta del proyecto.

Al volver a iniciar se crean el esquema y las cuentas iniciales.

## Problemas comunes

### Windows no reconoce `php`

Agregar la carpeta de PHP al `PATH` y abrir una terminal nueva. Verificar con:

```bat
php -v
```

### El puerto 8000 está ocupado

Iniciar con otro puerto usando las instrucciones de la sección anterior.

### El televisor no abre la pantalla

- Verificar que ambos equipos estén en la misma red.
- Confirmar la IP del servidor.
- Permitir el puerto en el firewall.
- Probar primero `http://localhost:8000/?action=pantalla` en el servidor.

### Error de permisos de SQLite

Asegurar que la carpeta del proyecto tenga permisos de escritura para el usuario que ejecuta PHP.

## Nota sobre publicación en internet

El servidor integrado de PHP (`php -S`) está pensado para uso local, demostración o red interna. Para publicar el sistema en internet se recomienda usar Apache o Nginx con PHP-FPM, HTTPS y una carpeta persistente para SQLite. También se pueden cambiar las contraseñas iniciales y restringir el acceso desde el administrador.
