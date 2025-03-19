<?php
session_start();

// Incluir el archivo de configuración de la base de datos
require 'config.php';

// Verificar si $conexion está definido
if (!isset($conexion)) {
    die("Error: La conexión a la base de datos no está disponible.");
}

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Buscar el usuario en la base de datos, incluyendo la columna 'role'
    $query = "SELECT id, username, password, nombre, role FROM usuarios WHERE username = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Autenticación exitosa
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nombre'] = $user['nombre'];
        // Guardar el rol en la sesión
        $_SESSION['role'] = $user['role'];

        // Redirigir al index o dashboard
        header('Location: index.php');
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.png">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="header-banner">
        Bienvenido Fersus!
    </div>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <label for="username">Usuario:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Iniciar Sesión</button>
        </form>
    </div>
    <footer class="footer">
        <p>&copy; 2025 Fersus & soursop. all rights reserved.</p>
    </footer>
</body>
</html>