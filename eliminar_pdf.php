<?php
session_start(); // Iniciar sesión para acceder al ID del usuario
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $pdfPath = $_POST['pdfPath'];

    // Obtener el ID del usuario que eliminó el PDF
    $usuario_borro = $_SESSION['user_id'];

    // Actualizar el campo usuario_borro en la base de datos
    $query = "UPDATE reportes SET usuario_borro = ? WHERE id = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ii", $usuario_borro, $id);
    $stmt->execute();

    // Eliminar el archivo PDF
    if (file_exists($pdfPath)) {
        if (unlink($pdfPath)) {
            // Eliminar el registro de la base de datos si el archivo se eliminó correctamente
            $query = "DELETE FROM reportes WHERE id = ?";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo "PDF y registro eliminados correctamente.";
            } else {
                echo "Error al eliminar el registro de la base de datos.";
            }
            $stmt->close();
        } else {
            echo "Error al eliminar el archivo PDF.";
        }
    } else {
        echo "El archivo PDF no existe.";
    }
} else {
    echo "Petición inválida.";
}

$conexion->close();
?>