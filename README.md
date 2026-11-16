# Sistema de Turnos para Consultorios

Sistema web desarrollado en **PHP con Programación Orientada a Objetos (POO)** y arquitectura **MVC** para la emisión de turnos presenciales, gestión de la cola FIFO y visualización en tiempo real de la sala de espera en un televisor o monitor.

## Requisitos

Para el paquete portable de Windows no se requiere instalar nada: incluye PHP 8.5 x64, SQLite y el componente de Visual C++ necesario.

Para ejecutar desde el código fuente:

- PHP 8.1 o superior (probado con PHP 8.5)
- Extensiones `pdo_sqlite`, `sqlite3` y `mbstring`
- Un navegador moderno

No requiere Composer ni servidor Apache: se ejecuta con el servidor integrado de PHP.

## Inicio rápido

### Windows (paquete portable, recomendado)

1. Descomprimir `SistemaTurnosConsultorios-Windows-x64.zip`.
2. Ejecutar `start.bat`. No es necesario instalar PHP.
3. Abrir `http://localhost:8000/` (el navegador se abre automáticamente).

### Windows con PHP instalado

1. Instalar PHP 8.1 o superior y habilitar `pdo_sqlite`, `sqlite3` y `mbstring`.
2. Abrir la carpeta del proyecto y ejecutar `start.bat`.
3. Abrir `http://localhost:8000/` en el navegador.

### Linux / macOS

```bash
cd SistemaTurnosConsultorios
chmod +x start.sh
./start.sh
```

Si el archivo no tiene permisos de ejecución, también puede iniciarse con:

```bash
bash start.sh
```

O manualmente:

```bash
php -S 0.0.0.0:8000 -t public
```

Abrir en el navegador:

- Sistema: `http://localhost:8000/`
- Pantalla de sala de espera (TV): `http://localhost:8000/?action=pantalla`

La base de datos `database.sqlite` se crea y se llena automáticamente en el primer acceso.

## Pantalla para televisor

1. Conectar el televisor o monitor a la red del consultorio.
2. Abrir en el navegador del televisor:

   ```
   http://IP_DEL_SERVIDOR:8000/?action=pantalla
   ```

3. Presionar **Pantalla completa** y **Activar sonido** una vez (los navegadores exigen una interacción del usuario para reproducir audio).
4. La pantalla se actualiza cada **2 segundos** y avisa con sonido y destello cuando se llama un turno nuevo.

Si otro equipo no puede abrir la dirección:

- Verificar que ambos equipos estén en la misma red.
- Permitir el puerto 8000 en el firewall del servidor.
- Confirmar la IP del servidor con `hostname -I` (Linux) o `ipconfig` (Windows).

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

## Usar varios roles a la vez

La sesión se guarda en el navegador. Para mantener dos roles abiertos al mismo tiempo:

1. Abrir el sistema en una ventana normal e iniciar sesión con el primer rol.
2. Abrir una ventana privada o de incógnito e iniciar sesión con el segundo rol.
3. La pantalla de TV puede permanecer abierta sin iniciar sesión.

También se pueden usar dos navegadores o dos perfiles distintos. Si se inicia sesión con otro rol en la misma ventana normal, la sesión anterior se reemplaza.

## Funcionalidades

### Autenticación y usuarios
- Inicio de sesión con sesión PHP segura y roles: `admin`, `recepcion`, `medico`.
- Contraseñas con `password_hash` / `password_verify`.
- Administrador: crear, editar, activar/desactivar usuarios y restablecer contraseñas.
- Protección para no desactivar la propia cuenta ni el último administrador activo.

### Recepción
- Emisión de turnos presenciales por especialidad.
- Código automático con prefijo y correlativo diario: `MG-01`, `PEDI-01`, `MI-01`, `OD-01`, `PSI-01`.
- Nombre de paciente opcional.
- Ticket imprimible.
- Cola de espera en vivo.

### Médico
- Botón **Llamar siguiente paciente** (FIFO por especialidad).
- El turno anterior pasa a `ATENDIDO` y desaparece de la pantalla pública.
- Botón **Paciente ausente**: marca `AUSENTE` y llama automáticamente al siguiente.
- Botón **Finalizar atención**.

### Pantalla pública
- Vista pública sin autenticación, adaptada a televisores.
- Muestra código actual, especialidad, médico y consultorio.
- Próximos turnos en espera.
- Códigos de color: gris para `ESPERA`, verde para `ATENDIENDO`.
- Sonido, destello, reloj y pantalla completa.

### Historial
- Turnos del día con filtros por especialidad y estado.
- Resumen de total, en espera, atendiendo, atendidos y ausentes.

## Estructura

```
app/
├── controllers/   Controladores MVC
├── helpers/       Respuestas JSON, CSRF y funciones
├── models/        Usuario (abstracta), Administrador, Recepcionista, Medico,
│                  Especialidad y Turno
├── services/      AuthService, GeneradorCodigoTurno y GestorColaTurnos
└── views/         Vistas PHP (login, recepción, médico, admin, historial, TV)
config/
└── database.php   Conexión PDO/SQLite, esquema, índices y datos iniciales
public/
├── index.php      Front controller y router
└── assets/        CSS y JavaScript
runtime/php/       PHP 8.5 x64 incluido en el paquete portable para Windows
portable/php.ini   Configuración usada por el PHP portable
start.sh           Inicio en Linux / macOS
start.bat          Inicio en Windows (usa runtime\php si existe)
build-portable.sh  Genera el ZIP portable de Windows en dist/
package.sh         Genera un ZIP sin PHP en dist/
LEEME.txt          Guía rápida para el usuario final
INSTALACION.md     Guía de instalación y uso
database.sqlite    Base de datos (se genera automáticamente)
```

## Lógica de la cola

- **FIFO** (`First-In, First-Out`): los turnos se atienden con `ORDER BY fecha_creacion ASC, id ASC`.
- `GeneradorCodigoTurno` usa una transacción `BEGIN IMMEDIATE` y `MAX(secuencial)` para evitar códigos duplicados en emisiones concurrentes.
- `GestorColaTurnos::llamarSiguiente()` finaliza el turno anterior del médico y activa el más antiguo de su especialidad.
- Índices únicos en la base de datos:
  - `UNIQUE(codigo, dia)` evita códigos repetidos.
  - `UNIQUE(medico_id) WHERE estado = 'ATENDIENDO'` evita que un médico atienda dos pacientes a la vez.

## Zona horaria

Por defecto se usa `America/Mexico_City`. Se puede cambiar al iniciar el servidor:

```bash
APP_TIMEZONE=America/Lima php -S 0.0.0.0:8000 -t public
```

El día del correlativo y del historial se calcula con la zona horaria configurada.

## Seguridad

- Consultas preparadas en todas las operaciones con la base de datos.
- Control de permisos por rol en cada vista y endpoint.
- Token CSRF en todas las operaciones de escritura.
- Cookies de sesión `HttpOnly` y `SameSite=Lax`.
- Validación y escapado de salidas.
- Cabeceras `Content-Security-Policy`, `X-Frame-Options` y `X-Content-Type-Options`.

## API interna

Las vistas consumen los siguientes endpoints JSON:

- `api_login`, `api_logout`, `api_me`
- `api_especialidades`, `api_medicos`
- `api_crear_turno`, `api_cola`, `api_turno_actual`
- `api_llamar_siguiente`, `api_marcar_ausente`, `api_finalizar`
- `api_historial`
- `api_tv` (público, solo lectura)
- `api_usuarios`, `api_usuario_guardar`, `api_usuario_estado`, `api_usuario_password`

## Paquete instalable

### Windows portable (recomendado para entregar)

```bash
./build-portable.sh
```

Se genera:

```
dist/SistemaTurnosConsultorios-Windows-x64.zip
```

Incluye PHP 8.5 para Windows x64, el instalador de Visual C++ y la aplicación. El usuario final solo descomprime y ejecuta `start.bat`; no necesita instalar PHP.

### Paquete sin PHP

```bash
./package.sh
```

Se genera:

```
dist/SistemaTurnosConsultorios.zip
```

Incluye la aplicación, los scripts de inicio y la documentación. Requiere PHP instalado en el equipo destino.

Ningún ZIP incluye `.git`, `runtime/` (salvo el portable), `.cache/` ni `database.sqlite`, por lo que la base se crea limpia en el primer acceso.

## Reiniciar la base de datos

Detener el servidor y eliminar los archivos generados:

```bash
rm -f database.sqlite database.sqlite-shm database.sqlite-wal
```

Al volver a iniciar, se crean el esquema y los usuarios iniciales.

## Pruebas sugeridas

1. Iniciar sesión como recepción y emitir un turno de Medicina general (`MG-01`).
2. Abrir la pantalla TV en otra pestaña o equipo.
3. Abrir una ventana privada e iniciar sesión como médico de Medicina general.
4. Presionar **Llamar siguiente paciente** y verificar que la TV muestra `MG-01` en verde en un máximo de 2 segundos.
5. Probar una especialidad distinta, por ejemplo Pediatría (`PEDI-01`), e iniciarla sesión con `pediatria@consultorio.local`.
6. Presionar **Paciente ausente** y confirmar que se llama automáticamente al siguiente turno.
7. Revisar el historial y el módulo de administración de usuarios.
