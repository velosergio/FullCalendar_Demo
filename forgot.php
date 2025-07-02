<?php
require_once 'auth.php';

// Si ya está logueado, redirigir al calendario
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Calendario</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-header">
            <h1><i class="bi bi-question-circle"></i> Recuperar Contraseña</h1>
            <p>Ingresa tu email y te enviaremos instrucciones para restablecer tu contraseña</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 
            <strong>Nota:</strong> Esta es una simulación. En un sistema real, se enviaría un email con un enlace para restablecer la contraseña.
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="forgot">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       placeholder="tu@email.com">
            </div>
            
            <button type="submit" class="btn-primary">
                <i class="bi bi-envelope"></i> Enviar Instrucciones
            </button>
        </form>
        
        <div class="divider">
            <span>o</span>
        </div>
        
        <div class="auth-links">
            <a href="login.php">
                <i class="bi bi-arrow-left"></i> Volver al login
            </a>
            <a href="register.php">
                <i class="bi bi-person-plus"></i> Crear cuenta
            </a>
        </div>
    </div>
</body>
</html> 