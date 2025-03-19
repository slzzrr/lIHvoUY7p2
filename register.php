<?php
session_start();

// Si el usuario ya está autenticado, redirigir a index.php
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for Fersus</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.png">
    <link rel="stylesheet" href="assets/css/register.css">
</head>
<body>
    <div class="register-container">
        <h2>Registro de Usuario</h2>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <form action="register_process.php" method="POST">
            <div class="form-group">
                <label for="username">Nombre de usuario:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmar contraseña:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <label for="nombre">Nombre completo:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div class="form-group">
                <label for="email">Correo electrónico:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <!-- 
                Si deseas que el usuario elija su rol, descomenta estas líneas:
            <div class="form-group">
                <label for="role">Rol:</label>
                <select id="role" name="role">
                    <option value="viewer">Viewer (default)</option>
                    <option value="colaborador">Colaborador</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            -->
            <button type="submit" class="btn">Registrarse</button>
        </form>
        <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>.</p>
    </div>
    <footer class="footer">
        <p>&copy; 2025 Soursop & Fersus. all rights reserved.</p>
    </footer>
</body>
</html>