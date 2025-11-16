# Gestor de Tareas (TaskManager)
Aplicación ligera para coordinar proyectos y tareas con autenticación, control de estados y comentarios, pensada para correr tanto en XAMPP como en un hosting shared (cPanel).

## Características
Registro, login, sesión y logout vía API (api/api.php, sesiones PHP).
CRUD de proyectos y tareas con prioridades, fechas límite, asignados y filtros en vivo.
Comentarios por tarea y orden inteligente (status + priority + due_date).
API REST JSON con manejo de errores consistente y soporte para PATCH parcial.
Esquema SQL optimizado (índices útiles en schema.sql) listo para importar.
Stack y estructura
Frontend: HTML5 + CSS3 + Fetch API nativa (index.html, styles.css).
Backend: PHP 8.x con MySQLi; sesión en servidor y endpoints REST (api/api.php y api/ping.php).
Base de datos: MySQL 8 o MariaDB 10, tablas users, projects, tasks, task_comments.
Archivos clave:
index.html: UI completa y lógica de consumo de API.
styles.css: estilo responsivo básico.
api/api.php: router REST, autenticación, controladores de proyectos/tareas/comentarios.
schema.sql: script para crear todas las tablas e índices.
Requisitos previos
PHP 8.1+ con extensiones mysqli y openssl.
Servidor web (Apache/Nginx) configurado para servir la carpeta pública.
MySQL/MariaDB accesible y un usuario con permisos de creación.
Archivo api/db.php (no incluido) con la conexión:
<?php
$mysqli = new mysqli('localhost','usuario','password','task_manager');
if ($mysqli->connect_errno) {
    http_response_code(500);
    die('Error de conexión: '.$mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
Opcional: XAMPP/LAMPP para entornos locales o cPanel para despliegue compartido.
Instalación local
Coloca el proyecto (por ejemplo CloudComputing1/) dentro de htdocs o la raíz de tu servidor web.
Crea la base de datos e importa schema.sql:
CREATE DATABASE task_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE task_manager;
SOURCE schema.sql;
Crea api/db.php con la conexión de arriba y actualiza credenciales.
Ajusta la constante API en index.html si cambiaste la ruta del backend.
Abre http://localhost/CloudComputing1/ (o la ruta configurada). Registra un usuario y empieza a crear proyectos/tareas.
Endpoints principales
POST auth/register – crea usuario.
POST auth/login / POST auth/logout / GET auth/me – ciclo de sesión.
GET/POST projects, PUT/PATCH/DELETE projects/{id} – gestión de proyectos.
GET/POST tasks, PUT/PATCH/DELETE tasks/{id} – tareas.
GET/POST tasks/{id}/comments – comentarios por tarea.
Todos devuelven JSON y requieren Content-Type: application/json. Excepto registro/login, el resto exige sesión activa.

## Despliegue recomendado (cPanel)
Sube todo el contenido a public_html/taskmanager/.
Crea la base en MySQL remoto e importa schema.sql.
Ajusta api/db.php con host, usuario y password proporcionados por el hosting.
Verifica la API con api/ping.php antes de abrir index.html.
## Abre en el navegador: http://localhost/taskmanager
