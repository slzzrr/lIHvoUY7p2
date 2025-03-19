<?php
session_start(); // Iniciar sesión para acceder al ID del usuario
include 'config.php';

// Obtener el último folio
$query = "SELECT MAX(folio) as max_folio FROM reportes";
$result = $conexion->query($query);
if (!$result) {
    die("Error en la consulta del folio: " . $conexion->error);
}
$row = $result->fetch_assoc();
$nuevo_folio = $row['max_folio'] + 1;

// Datos del formulario
$fecha_carga = $_POST['fechaCarga'];
$fecha_entrega = $_POST['fechaEntrega'];
$nombre_remitente = $_POST['nombreRemitente'];
$direccion_remitente = $_POST['direccionRemitente'];
$nombre_destinatario = $_POST['nombreDestinatario'];
$direccion_destinatario = $_POST['direccionDestinatario'];
$nombre_recibe = $_POST['nombreRecibe'];
$recibo_completo = isset($_POST['reciboCompleto']) ? 1 : 0;
$recibo_sin_danos = isset($_POST['reciboSinDanos']) ? 1 : 0;
$observaciones = $_POST['observaciones'];
$nombre_operador = $_POST['nombreOperador'];
$placas_unidad = $_POST['placasUnidad'];
$ruta_pdf = ""; // Generar y guardar el PDF

// Guardar reporte principal
$query = "INSERT INTO reportes (fecha_carga, fecha_entrega, hora_entrega, folio, nombre_remitente, direccion_remitente, nombre_destinatario, direccion_destinatario, nombre_recibe, campania, hora_recepcion, recibo_completo, recibo_sin_danos, observaciones, nombre_operador, placas_unidad, ruta_pdf, observaciones_recibo, usuario_genero) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conexion->prepare($query);
if (!$stmt) {
    die("Error al preparar la consulta: " . $conexion->error);
}

// Obtener el ID del usuario que generó el PDF
$usuario_genero = $_SESSION['user_id'];

$stmt->bind_param("sssissssisssisssssi", $fecha_carga, $fecha_entrega, $hora_entrega, $nuevo_folio, $nombre_remitente, $direccion_remitente, $nombre_destinatario, $direccion_destinatario, $nombre_recibe, $campania, $hora_recepcion, $recibo_completo, $recibo_sin_danos, $observaciones, $nombre_operador, $placas_unidad, $ruta_pdf, $observaciones_recibo, $usuario_genero);

if (!$stmt->execute()) {
    die("Error al ejecutar la consulta: " . $stmt->error);
}
$reporte_id = $stmt->insert_id;

// Guardar productos
$index = 1;
while (isset($_POST['np' . $index])) {
    $np = $_POST["np$index"];
    $descripcion = $_POST["descripcion$index"];
    $cantidad = $_POST["cantidad$index"];
    $so = $_POST["so$index"];
    $rma = $_POST["rma$index"];

    $query = "INSERT INTO productos (reporte_id, np, descripcion, cantidad, so, rma) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($query);
    if (!$stmt) {
        die("Error al preparar la consulta de productos: " . $conexion->error);
    }

    $stmt->bind_param("ississ", $reporte_id, $np, $descripcion, $cantidad, $so, $rma);
    if (!$stmt->execute()) {
        die("Error al ejecutar la consulta de productos: " . $stmt->error);
    }
    $index++;
}

// Cerrar la conexión
$stmt->close();
$conexion->close();

// Redirigir a DescargarReporte_x_fecha_PDF.php para generar el PDF
header("Location: DescargarReporte_x_fecha_PDF.php?reporte_id=" . $reporte_id);
exit;
?>