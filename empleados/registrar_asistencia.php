<?php
session_start();
include '../config.php';

// Verificar si hay sesión
if (!isset($_SESSION['user_id'])) {
    die("Acceso denegado.");
}

// Obtener el ID del empleado desde la URL
$employeeId = $_GET['id'] ?? 0;
if (!$employeeId) {
    die("ID inválido.");
}

// Buscar la hora de entrada del empleado en la BD
$stmt = $conexion->prepare("SELECT hora_entrada FROM empleados WHERE id = ?");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$res = $stmt->get_result();
$emp = $res->fetch_assoc();
$stmt->close();

if (!$emp) {
    die("Empleado no encontrado.");
}

// Hora de entrada oficial
$horaEntrada = $emp['hora_entrada'] ?? '08:00'; // Si no existe, por defecto 08:00

// Obtener fecha y hora actual desde PHP (ya con la zona horaria de México)
$fechaHoy = date('Y-m-d');    // p.ej. "2025-03-18"
$horaAhora = date('H:i:s');   // p.ej. "01:05:23"

// Determinar si llega a tiempo o tarde
$estado = ($horaAhora <= $horaEntrada) ? "A tiempo" : "Tarde";

// Insertar registro en la tabla 'asistencias'
$stmt2 = $conexion->prepare("
    INSERT INTO asistencias (empleado_id, fecha, hora_registro, estado)
    VALUES (?, ?, ?, ?)
");
$stmt2->bind_param("isss", $employeeId, $fechaHoy, $horaAhora, $estado);
$stmt2->execute();
$stmt2->close();

// Devolvemos un mensaje
echo "Asistencia registrada: " . $estado;
?>