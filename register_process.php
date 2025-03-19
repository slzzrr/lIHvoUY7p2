<?php
session_start();
include 'config.php';

// Verificar si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);

    // Si no usas un <select> para el rol, define 'viewer' por defecto:
    $role = 'viewer';

    // Si quisieras permitir que el usuario elija su rol, descomenta:
    /*
    if (isset($_POST['role'])) {
        $role = $_POST['role'];
    } else {
        $role = 'viewer'; // por defecto
    }
    */

    // Validar los datos
    if (empty($username) || empty($password) || empty($confirm_password) || empty($nombre) || empty($email)) {
        $_SESSION['error'] = "Todos los campos son obligatorios.";
        header('Location: register.php');
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Las contraseñas no coinciden.";
        header('Location: register.php');
        exit();
    }

    // Verificar si el nombre de usuario ya existe
    $query = "SELECT id FROM usuarios WHERE username = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "El nombre de usuario ya está en uso.";
        header('Location: register.php');
        exit();
    }

    // Cifrar la contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insertar el nuevo usuario en la base de datos, incluyendo la columna 'role'
    $query = "INSERT INTO usuarios (username, password, nombre, email, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('sssss', $username, $hashed_password, $nombre, $email, $role);

    if ($stmt->execute()) {
        // Registro exitoso, redirigir al login
        $_SESSION['success'] = "Registro exitoso. Por favor, inicia sesión.";
        header('Location: login.php');
        exit();
    } else {
        $_SESSION['error'] = "Error al registrar el usuario. Inténtalo de nuevo.";
        header('Location: register.php');
        exit();
    }

    $stmt->close();
    $conexion->close();
} else {
    // Si no se envió el formulario, redirigir al registro
    header('Location: register.php');
    exit();
}
