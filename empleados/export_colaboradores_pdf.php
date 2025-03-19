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

// Ajusta la ruta a TCPDF
require_once(__DIR__ . '/../tcpdf/tcpdf.php');

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Soursop Solutions');
$pdf->SetAuthor('Fersus');
$pdf->SetTitle('Colaboradores');
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

// Título
$pdf->SetFont('helvetica','B',14);
$pdf->Cell(0, 10, 'Listado de Colaboradores', 0, 1, 'C');
$pdf->Ln(5);

// Construimos el HTML de la tabla
$pdf->SetFont('helvetica','',10);

$html = '<table border="1" cellpadding="4" cellspacing="0" width="100%">';
$html .= '<thead>
  <tr style="background-color:#f2f2f2;">
    <th>ID</th>
    <th>Nombre</th>
    <th>Apellido</th>
    <th>INE (Status)</th>
    <th>Teléfono</th>
    <th>Puesto</th>
  </tr>
</thead>
<tbody>';

while ($row = $result->fetch_assoc()) {
    // Determinar INE (Status)
    $ineStatus = "No hay INE";
    if (($row['ine_frontal'] && file_exists($row['ine_frontal'])) ||
        ($row['ine_trasero'] && file_exists($row['ine_trasero']))) {
        $ineStatus = "Sí hay INE";
    }

    $html .= '<tr>
      <td>'. $row['id'] .'</td>
      <td>'. htmlspecialchars($row['nombre']) .'</td>
      <td>'. htmlspecialchars($row['apellido']) .'</td>
      <td>'. $ineStatus .'</td>
      <td>'. htmlspecialchars($row['telefono']) .'</td>
      <td>'. htmlspecialchars($row['puesto']) .'</td>
    </tr>';
}
$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('colaboradores.pdf','I');
exit();
