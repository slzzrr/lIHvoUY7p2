<?php
session_start();
include '../auth.php';
include '../config.php';
requireRoles(['admin']);

$search = $_GET['search'] ?? '';

$sql = "SELECT e.id, CONCAT(e.nombre, ' ', e.apellido) AS empleado, s.salario, s.periodicidad, s.fecha_registro, e.email 
        FROM empleados e
        LEFT JOIN salarios s ON e.id = s.empleado_id
        WHERE 1";
if ($search !== '') {
    $searchLike = "%$search%";
    $sql .= " AND (e.nombre LIKE ? OR e.apellido LIKE ?)";
}
$sql .= " ORDER BY e.id DESC";

$stmt = $conexion->prepare($sql);
if ($search !== '') {
    $stmt->bind_param('ss', $searchLike, $searchLike);
}
$stmt->execute();
$result = $stmt->get_result();

require_once(__DIR__ . '/../tcpdf/tcpdf.php');

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Soursop Solutions');
$pdf->SetAuthor('Fersus');
$pdf->SetTitle('Listado de Colaboradores con Salario');
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

// Título
$pdf->SetFont('helvetica','B',14);
$pdf->Cell(0, 10, 'Listado de Colaboradores con Salario', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('helvetica','',10);

$html = '<table border="1" cellpadding="4" cellspacing="0" width="100%">';
$html .= '<thead>
  <tr style="background-color:#333; color:#fff;">
    <th>ID</th>
    <th>Colaborador</th>
    <th>Salario (MXN)</th>
    <th>Periodicidad</th>
    <th>Fecha de Registro</th>
    <th>Email</th>
  </tr>
</thead>
<tbody>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
      <td>' . $row['id'] . '</td>
      <td>' . htmlspecialchars($row['empleado']) . '</td>
      <td>' . (isset($row['salario']) ? '$' . number_format($row['salario'], 2) : 'N/A') . '</td>
      <td>' . (isset($row['periodicidad']) ? htmlspecialchars($row['periodicidad']) : 'N/A') . '</td>
      <td>' . (isset($row['fecha_registro']) ? htmlspecialchars($row['fecha_registro']) : 'N/A') . '</td>
      <td>' . htmlspecialchars($row['email'] ?? 'N/A') . '</td>
    </tr>';
}
$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('salarios.pdf','I');
exit();
