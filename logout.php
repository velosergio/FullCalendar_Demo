<?php
require_once 'auth.php';

// Cerrar sesión
logoutUser();

// Redirigir al login
header('Location: login.php');
exit;
?> 