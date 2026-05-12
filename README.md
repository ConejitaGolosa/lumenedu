# LumenEdu

Plataforma educativa en PHP puro con patrón MVC, inspirada en Patreon pero exclusivamente para educación. Los profesores (Creadores) publican videos y los alumnos (Suscriptores) pagan suscripción para acceder al contenido.

## Stack

- PHP + MySQLi (prepared statements), sin framework
- MySQL — base de datos definida en `Project.sql`
- Arquitectura MVC manual: `apps/controller/`, `apps/models/`, `apps/views/`
- `index.php` como front controller (enrutador vía `?page=` y `?action=`)
- Sesiones PHP nativas

## Roles de usuario

| Rol | Descripción |
|-----|-------------|
| Creador | Profesor que sube y publica videos |
| Suscriptor | Alumno que paga para acceder al contenido |
| Administrador | Revisa y aprueba los videos antes de que se publiquen |

## Requisitos

- **WampServer** (incluye Apache + PHP + MySQL + phpMyAdmin) — versión 3.x o superior
- PHP 7.4 o superior (WampServer 3.x trae PHP 8.x por defecto, es compatible)
- Navegador web moderno

---

## Instalación local paso a paso (WampServer)

### 1. Instalar WampServer

Descarga WampServer desde su sitio oficial (busca "WampServer download") e instálalo con las opciones por defecto. Al terminar, ábrelo — el ícono en la barra de tareas debe ponerse **verde** antes de continuar.

> Si el ícono queda en naranja o rojo, revisa que ningún otro programa esté usando el puerto 80 (Skype, IIS, etc.). Puedes cambiar el puerto de Apache en el panel de WampServer si es necesario.

---

### 2. Clonar el repositorio

Abre una terminal (CMD o PowerShell) y clona el proyecto **dentro de la carpeta `www` de WampServer**, que normalmente está en:

```
C:\wamp64\www\
```

```bash
cd C:\wamp64\www
git clone https://github.com/ConejitaGolosa/lumenedu.git
```

Esto crea la carpeta `C:\wamp64\www\lumenedu\` con todos los archivos del proyecto.

---

### 3. Crear la base de datos

1. Con WampServer corriendo (ícono verde), abre **phpMyAdmin** desde el navegador: `http://localhost/phpmyadmin`
2. Usuario: `root` — Contraseña: *(vacía por defecto en WampServer)*
3. En el menú izquierdo haz clic en **"Nueva"** para crear una base de datos, ponle el nombre **`Project`** y selecciona cotejamiento `utf8mb4_unicode_ci`. Luego haz clic en **Crear**.
4. Con la BD `Project` seleccionada, ve a la pestaña **Importar**.
5. Haz clic en **"Seleccionar archivo"** y elige el archivo `Project.sql` de la carpeta del proyecto.
6. Desplázate al final y haz clic en **Importar**. Esto crea todas las tablas base.
7. Repite el paso 4–6 pero ahora con el archivo `migration_v2.sql`. Este agrega las tablas de videos, tickets, solicitudes, comentarios y notificaciones.

> Importante: importa primero `Project.sql` y luego `migration_v2.sql`, en ese orden.

---

### 4. Configurar la conexión a la base de datos

1. Dentro de la carpeta del proyecto, entra a `apps/config/`.
2. Copia el archivo `config.example.php` y renómbralo como `config.php`.
3. Ábrelo con cualquier editor de texto y verifica que los valores sean:

```php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');   // usuario de MySQL (por defecto en WampServer)
define('DB_PASS', '');       // contraseña vacía por defecto en WampServer
define('DB_NAME', 'Project');
```

Si le pusiste contraseña a tu MySQL de WampServer, colócala en `DB_PASS`.

---

### 5. Aumentar el límite de subida de archivos (para videos)

El proyecto permite subir videos de hasta **500 MB**. WampServer tiene un límite muy bajo por defecto. Para aumentarlo:

1. Haz clic izquierdo en el ícono de WampServer en la barra de tareas.
2. Ve a **PHP → php.ini** (abre el archivo de configuración).
3. Busca y cambia estas líneas (usa Ctrl+F en el editor):

```ini
upload_max_filesize = 512M
post_max_size = 512M
max_execution_time = 300
max_input_time = 300
```

4. Guarda el archivo y **reinicia todos los servicios** desde el menú de WampServer (clic izquierdo → "Reiniciar todos los servicios").

---

### 6. Crear la carpeta de uploads

La carpeta donde se guardan los videos subidos no viene en el repositorio (está en `.gitignore`). Créala manualmente:

```
C:\wamp64\www\lumenedu\public\uploads\videos\
```

O desde PowerShell:

```powershell
mkdir C:\wamp64\www\lumenedu\public\uploads\videos
```

---

### 7. Acceder al proyecto

Abre el navegador y entra a:

```
http://localhost/lumenedu/
```

Deberías ver la página principal de LumenEdu. Si ves un error de conexión a la BD, revisa el paso 4.

---

### Problemas frecuentes

| Problema | Solución |
|----------|----------|
| Ícono de WampServer naranja | Otro programa usa el puerto 80. Cierra Skype o IIS, o cambia el puerto de Apache. |
| Error "No such file: config.php" | Olvidaste copiar `config.example.php` como `config.php` en `apps/config/`. |
| Error de conexión a BD | Verifica que MySQL esté activo (ícono verde) y que `DB_PASS` sea correcto en `config.php`. |
| Videos no se suben | Revisa que `public/uploads/videos/` exista y que hayas aumentado los límites en `php.ini`. |
| Página en blanco | Activa la visualización de errores en WampServer: menú → PHP → `display_errors = On`. |

## Estructura del proyecto

```
lumen/
├── index.php                  # Front controller y enrutador
├── Project.sql                # Esquema completo de la BD
├── migration_v2.sql           # Migraciones de la versión 2
├── public/
│   └── style.css              # Estilos globales
├── apps/
│   ├── config/
│   │   ├── config.php         # Credenciales BD (no versionado)
│   │   └── config.example.php # Plantilla de config
│   ├── models/
│   │   ├── configConexion.php
│   │   ├── modelUser.php
│   │   ├── modelVideo.php
│   │   ├── modelComentario.php
│   │   ├── modelNotificacion.php
│   │   ├── modelTicket.php
│   │   └── modelSolicitudClase.php
│   ├── controller/
│   │   ├── controllerUser.php
│   │   ├── controllerVideo.php
│   │   ├── controllerAdmin.php
│   │   └── controllerTicket.php
│   └── views/
│       ├── viewHome.php
│       ├── viewLogin.php
│       ├── viewRegistro.php
│       ├── viewConfirmacion.php
│       ├── viewAbout.php
│       ├── viewVideos.php
│       ├── viewVideo.php
│       ├── viewSubirVideo.php
│       ├── viewMisVideos.php
│       ├── viewPublicarVideo.php
│       ├── viewAdminPanel.php
│       ├── viewNotificaciones.php
│       ├── viewTickets.php
│       └── viewSolicitudes.php
```

---

## Bitácora de cambios

### 2026-05-12 15:10 — Documentación de instalación ampliada

**Archivos modificados:** `README.md`

Se reescribió la sección de instalación local con instrucciones detalladas paso a paso para **WampServer**, incluyendo:
- Requisitos de versión (WampServer 3.x, PHP 7.4+)
- Cómo clonar el repo dentro de `C:\wamp64\www\`
- Importación de `Project.sql` y `migration_v2.sql` en orden correcto desde phpMyAdmin
- Configuración de `config.php` con credenciales por defecto de WampServer
- Ajuste de `php.ini` para permitir subida de videos de hasta 512 MB
- Creación manual de la carpeta `public/uploads/videos/`
- Tabla de problemas frecuentes con sus soluciones

---

### 2026-05-12 14:55 — Commit inicial

**Archivos añadidos:** 31 archivos (2 539 líneas)

Primer commit con la plataforma completa. Módulos incluidos:

- **Auth** (`controllerUser`, `modelUser`): registro con bcrypt, login, logout, flash messages, redirección por sesión.
- **Videos** (`controllerVideo`, `modelVideo`, `modelComentario`): subida de video (mp4/avi/mov/mkv/webm, máx 500 MB), queda en estado `Pendiente` hasta revisión; publicación con título, descripción y privacidad (`Publico`/`Suscriptores`/`Privado`); comentarios (máx 1 024 caracteres).
- **Administración** (`controllerAdmin`): panel de revisión de videos pendientes; el admin aprueba o rechaza con motivo obligatorio; se registra en tabla `RevisionVideo` y se notifica al profesor.
- **Tickets** (`modelTicket`, `controllerTicket`): cada Suscriptor puede usar hasta 3 tickets por mes para desbloquear el contenido de un Creador específico.
- **Solicitudes de clase virtual** (`modelSolicitudClase`, `controllerTicket`): el alumno propone una fecha al profesor (requiere ticket activo); el profesor responde con `Aceptada`, `Rechazada` o `AceptadaConCondiciones`.
- **Notificaciones** (`modelNotificacion`): badge en el nav con contador de no leídas; generadas automáticamente en cada evento clave (video aprobado/rechazado, solicitud de clase, respuesta del profesor).
- **Enrutador** (`index.php`): guard contra archivos que exceden `post_max_size`, nav dinámica por rol, flash messages.

**Repositorio creado en:** https://github.com/ConejitaGolosa/lumenedu
