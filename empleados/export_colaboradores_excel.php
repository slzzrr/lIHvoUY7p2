<?php
session_start();
include '../auth.php';
include '../config.php';
requireRoles(['admin']);

$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM empleados WHERE 1";
if ($search !== '') {
    $searchLike = "%$search%";
    $sql .= " AND (nombre LIKE ? OR apellido LIKE ? OR ine LIKE ?)";
}
$sql .= " ORDER BY id DESC";

$stmt = $conexion->prepare($sql);
if ($search !== '') {
    $stmt->bind_param('sss', $searchLike, $searchLike, $searchLike);
}
$stmt->execute();
$result = $stmt->get_result();

// Cabeceras para Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=colaboradores.xls");

// Inicio del HTML
echo '<html><head><meta charset="UTF-8"></head>';
echo '<body style="font-family:Arial, sans-serif;">';

// Tabla con estilo básico
echo '<table border="1" style="border-collapse:collapse; width:100%;">';

// Fila de encabezado con título (puedes agregar un logo con una URL si deseas)
echo '<tr style="background-color:#f2f2f2;">';
echo '<td colspan="6" style="text-align:center; padding:10px;">';
echo '<h2 style="margin:5px 0;">Listado de Colaboradores</h2>';
echo '</td></tr>';

// Fila de cabeceras de la tabla
echo '<tr style="background-color:#333; color:#fff;">';
echo '<th>ID</th>';
echo '<th>Nombre</th>';
echo '<th>Apellido</th>';
echo '<th>INE (Status)</th>';
echo '<th>Teléfono</th>';
echo '<th>Puesto</th>';
echo '</tr>';

// Filas con datos
while ($row = $result->fetch_assoc()) {
    // Determinar INE (Status)
    $ineStatus = "No hay INE";
    if (($row['ine_frontal'] && file_exists($row['ine_frontal'])) ||
        ($row['ine_trasero'] && file_exists($row['ine_trasero']))) {
        $ineStatus = "Sí hay INE";
    }

    echo '<tr>';
    echo '<td>'. $row['id'] .'</td>';
    echo '<td>'. htmlspecialchars($row['nombre']) .'</td>';
    echo '<td>'. htmlspecialchars($row['apellido']) .'</td>';
    echo '<td>'. $ineStatus .'</td>';
    echo '<td>'. htmlspecialchars($row['telefono']) .'</td>';
    echo '<td>'. htmlspecialchars($row['puesto']) .'</td>';
    echo '</tr>';
}

echo '</table>';
echo '</body></html>';
exit();