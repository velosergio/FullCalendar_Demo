<?php
// Configuración de la base de datos para el sistema de calendario general

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'calendar');

// Crear conexión
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar conexión
if (!$conn) {
    die('Error de conexión a la base de datos: ' . mysqli_connect_error());
}

// Establecer zona horaria por defecto
// Puedes cambiarla según tu país
date_default_timezone_set('America/Bogota');
