Gestor de Tareas (TaskManager)

Aplicación ligera para coordinar proyectos y tareas con autenticación, control de estados y comentarios. Diseñada para funcionar tanto en XAMPP como en un hosting compartido (cPanel).

✨ Características principales

Registro, login, sesión y logout vía API (PHP + sesiones).

CRUD de proyectos y tareas con:

prioridades

fechas límite

asignados

filtros en vivo

Comentarios por tarea y orden inteligente (status + priority + due_date).

API REST JSON con manejo de errores y soporte para PATCH parcial.

Esquema SQL optimizado con índices listos para producción.

Frontend liviano con HTML5 + CSS3 + Fetch API nativa.

📁 Estructura del proyecto
Frontend

index.html: Interfaz completa y lógica de consumo de API.

styles.css: Estilo responsivo básico.

Backend

api/api.php: Router REST (usuarios, proyectos, tareas y comentarios).

api/ping.php: Endpoint de prueba.

api/db.php: Archivo de conexión (no incluido).

Base de datos

schema.sql: Script para crear todas las tablas e índices (users, projects, tasks, task_comments).

✔️ Requisitos previos

PHP 8.1+ con mysqli y openssl.

Servidor web Apache o Nginx.

MySQL 8 o MariaDB 10.

Usuario con permisos para crear BD.

Archivo api/db.php requerido:

<?php
$mysqli = new mysqli('localhost','usuario','password','task_manager');
if ($mysqli->connect_errno) {
    http_response_code(500);
    die('Error de conexión: '.$mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');


Opcional: XAMPP/LAMPP para entorno local o cPanel para hosting.

🖥️ Instalación local (XAMPP)

Copiar el proyecto (ejemplo: CloudComputing1/) dentro de htdocs.

Crear la base de datos e importar schema.sql:

CREATE DATABASE task_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE task_manager;
SOURCE schema.sql;


Crear api/db.php con tus credenciales.

Si cambiaron rutas, actualizar la constante API en index.html.

Abrir en el navegador:

👉 http://localhost/CloudComputing1/

Registrar un usuario y comenzar a usar proyectos/tareas.

🔌 Endpoints principales de la API
Autenticación

POST auth/register

POST auth/login

POST auth/logout

GET auth/me

Proyectos

GET/POST projects

PUT/PATCH/DELETE projects/{id}

Tareas

GET/POST tasks

PUT/PATCH/DELETE tasks/{id}

Comentarios

GET/POST tasks/{id}/comments

Todos los endpoints devuelven JSON y requieren Content-Type: application/json.
Excepto registro/login, el resto exige sesión activa.

🚀 Despliegue recomendado (cPanel)

Subir todo el proyecto a public_html/taskmanager/.

Crear la base MySQL desde el panel y importar schema.sql.

Configurar api/db.php con:

host del servidor MySQL

usuario

contraseña

Probar la API con:

👉 https://tudominio.com/taskmanager/api/ping.php

Abrir la aplicación en el navegador:

👉 https://tudominio.com/taskmanager/

🌐 Acceso rápido en local
http://localhost/taskmanager

