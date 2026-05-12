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

## Instalación local

1. Clona el repositorio
2. Copia `apps/config/config.example.php` → `apps/config/config.php` y ajusta las credenciales de MySQL
3. Importa `Project.sql` en tu servidor MySQL (crea la BD `Project`)
4. Si actualizas desde una versión anterior, ejecuta también `migration_v2.sql`
5. Apunta el servidor web (Apache/Nginx) a la carpeta raíz del proyecto
6. Accede desde `http://localhost/`

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
