<?php
session_start();
require_once __DIR__ . '/config/config.php';

// Función para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para generar token seguro
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Función para redirigir si no está logueado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Función para obtener usuario actual
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $conn;
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT id, email, created_at FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Función para registrar usuario
function registerUser($email, $password) {
    global $conn;
    
    // Validar email
    if (!isValidEmail($email)) {
        return ['success' => false, 'message' => 'Email inválido'];
    }
    
    // Verificar si el email ya existe
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return ['success' => false, 'message' => 'Este email ya está registrado'];
    }
    
    // Hash de la contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar usuario
    $sql = "INSERT INTO users (email, password) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $email, $hashed_password);
    
    if (mysqli_stmt_execute($stmt)) {
        return ['success' => true, 'message' => 'Usuario registrado correctamente'];
    } else {
        return ['success' => false, 'message' => 'Error al registrar usuario'];
    }
}

// Función para login
function loginUser($email, $password) {
    global $conn;
    
    // Buscar usuario por email
    $sql = "SELECT id, email, password FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if (!$user) {
        return ['success' => false, 'message' => 'Email o contraseña incorrectos'];
    }
    
    // Verificar contraseña
    if (password_verify($password, $user['password'])) {
        // Iniciar sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        return ['success' => true, 'message' => 'Login exitoso'];
    } else {
        return ['success' => false, 'message' => 'Email o contraseña incorrectos'];
    }
}

// Función para logout
function logoutUser() {
    session_destroy();
    return ['success' => true, 'message' => 'Sesión cerrada correctamente'];
}

// Función para recuperar contraseña (simulada)
function forgotPassword($email) {
    global $conn;
    
    // Verificar si el email existe
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        return ['success' => false, 'message' => 'Email no encontrado'];
    }
    
    // En un sistema real, aquí se enviaría un email con un enlace de recuperación
    // Por ahora, solo simulamos el envío
    return ['success' => true, 'message' => 'Si el email existe, recibirás instrucciones para recuperar tu contraseña'];
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'register':
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $error = 'Todos los campos son obligatorios';
            } elseif (strlen($password) < 6) {
                $error = 'La contraseña debe tener al menos 6 caracteres';
            } else {
                $result = registerUser($email, $password);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'login':
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $error = 'Todos los campos son obligatorios';
            } else {
                $result = loginUser($email, $password);
                if ($result['success']) {
                    header('Location: index.php');
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'forgot':
            $email = trim($_POST['email'] ?? '');
            
            if (empty($email)) {
                $error = 'El email es obligatorio';
            } else {
                $result = forgotPassword($email);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
            break;
    }
}
?> 