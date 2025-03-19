<?php
session_start();
include '../auth.php';
include '../config.php';
requireRoles(['admin']);

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

// 1) OBTENER DATOS DEL EMPLEADO
$empleado_id = $_GET['empleado_id'] ?? 0;
if (!$empleado_id) {
    die("Empleado no seleccionado.");
}

$stmt = $conexion->prepare("SELECT * FROM empleados WHERE id = ?");
$stmt->bind_param("i", $empleado_id);
$stmt->execute();
$res = $stmt->get_result();
$emp = $res->fetch_assoc();
$stmt->close();

if (!$emp) {
    die("Empleado no encontrado.");
}

// Asegúrate de tener la columna 'email' en la tabla
$email = $emp['email'] ?? '';

// 2) RUTAS DE IMÁGENES (TCPDF requiere rutas locales)
$frontBg = __DIR__ . '/../assets/img/bscard.png';
$logoPath = __DIR__ . '/../assets/img/logo.png';
$fotoPath = (!empty($emp['foto']) && file_exists($emp['foto'])) ? $emp['foto'] : '';

// 3) INCLUIR TCPDF
require_once('../tcpdf/tcpdf.php');

// 4) CONFIGURAR PDF
$customSize = array(54, 86);
$pdf = new TCPDF('P', 'mm', $customSize, true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);

// -------------------------------------------------
// PÁGINA FRONTAL
// -------------------------------------------------
$pdf->AddPage();

/*
   DISEÑO (frontal):
   - bscard.png como fondo total (0,0, 54x86).
   - NO óvalo/ellipse extra: el círculo ya está en la imagen.
   - Foto dentro del círculo: ajustamos X=16, Y=18, ancho=22 (modifica si necesario).
   - QR debajo de la foto (aprox. Y=43).
   - "fersus.com.mx" un poco más abajo (Y=70).
   - Nombre y puesto debajo (Y=75 y Y=79).
   - Tel/email etc. (Y=83).
   Ajusta X/Y si algo no luce bien alineado con tu background.
*/

// 1) Imagen de fondo (frontal)
if (file_exists($frontBg)) {
    $pdf->Image($frontBg, 0, 0, 54, 86, '', '', '', false, 300);
}
// 2) Texto "FERSUS TRANSPORTES" centrado en la parte superior (color blanco)
$pdf->SetFont('helvetica','B',10);
$pdf->SetTextColor(0,0,0);
$pdf->SetXY(0, 3);
$pdf->Cell(54, 5, "FERSUS TRANSPORTES", 0, 0, 'C', false);

// 3) Logo centrado debajo del texto (X=(54-15)/2=19.5, Y=10, ancho=15)
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 19.5, 10, 15, 0, '', '', '', false, 300);
}
// 4) Foto en el círculo
//   Ajustar X=16, Y=18, ancho=22 para encajar en el círculo de bscard.png
if ($fotoPath && file_exists($fotoPath)) {
    $pdf->Image($fotoPath, 16, 18, 22, 0, '', '', '', false, 300);
} else {
    // rectángulo gris "Sin Foto"
    $pdf->SetFillColor(180,180,180);
    $pdf->Rect(16, 18, 22, 22, 'F');
    $pdf->SetFont('helvetica','',7);
    $pdf->SetTextColor(0,0,0);
    $pdf->Text(18, 28, "Sin Foto");
}

// 5) QR debajo de la foto
// Utilizamos un string fijo y estructurado para generar un QR único y constante para cada empleado
$qrData = "EMPLOYEE:" . $emp['id'] . "|NAME:" . trim($emp['nombre'] . " " . $emp['apellido']) . "|POSITION:" . $emp['puesto'] . "|EMAIL:" . $email;
$qrStyle = array(
    'border' => 0,
    'vpadding' => 'auto',
    'hpadding' => 'auto',
    'fgcolor' => array(0,0,0),
    'bgcolor' => false,
    'module_width' => 1,
    'module_height' => 1
);
$pdf->write2DBarcode($qrData, 'QRCODE,H', 19.5, 43, 15, 15, $qrStyle, 'N');
// 5) Nombre y puesto
//   Nombre en Y=75, puesto en Y=79
$pdf->SetFont('helvetica','B',10);
$pdf->SetXY(0, 63.1);
$pdf->Cell(54, 4, $emp['nombre'] . ' ' . $emp['apellido'], 0, 0, 'C', false);

$pdf->SetFont('helvetica','',8);
$pdf->SetXY(0, 66);
$pdf->Cell(54, 4, $emp['puesto'], 0, 0, 'C', false);

// 6) Datos de contacto (tel, email, dirección) en Y=83
$pdf->SetFont('helvetica','',7);
$pdf->SetXY(5, 83);
$pdf->Cell(44, 3, "Tel: +52-722-480-4511 | info@fersus.com.mx", 0, 0, 'C', false);

// -------------------------------------------------
// PÁGINA TRASERA - TARJETA
// -------------------------------------------------
$pdf->AddPage();

// 1) Fondo blanco
$pdf->SetFillColor(255,255,255);
$pdf->Rect(0, 0, 54, 86, 'F');

// 2) Patrón de logos en baja opacidad
if (file_exists($logoPath)) {
    $pdf->SetAlpha(0.05);
    for ($y=0; $y<86; $y+=15) {
        for ($x=0; $x<54; $x+=15) {
            $pdf->Image($logoPath, $x+2, $y+2, 10, 0, '', '', '', false, 300);
        }
    }
    $pdf->SetAlpha(1);
}

// 3) Título "Uso de la Tarjeta" centrado
$pdf->SetFont('helvetica','B',10);
$pdf->SetTextColor(0,0,0);
$pdf->Text(7, 20, "Uso de la Tarjeta");

// Texto explicativo
$pdf->SetFont('helvetica','',8);
$pdf->SetXY(5, 30);
$pdf->MultiCell(44, 4, "Esta tarjeta es para identificación interna y representación en la empresa. No tiene validez oficial fuera de la misma.", 0, 'C', false);

// 4) Recuadro para ID en la esquina superior derecha (color #333)
$pdf->SetFillColor(51,51,51);
$pdf->Rect(40, 3, 12, 8, 'DF');
$pdf->SetFont('helvetica','B',8);
$pdf->SetTextColor(255,255,255);
$pdf->SetXY(40, 3);
$pdf->Cell(12, 8, $emp['id'], 0, 0, 'C', false);

// 5) Línea para la firma y debajo el nombre del empleado
$pdf->SetDrawColor(0,0,0);
$pdf->Line(5, 66, 49, 66);

$pdf->SetFont('helvetica','B',8);
$pdf->SetTextColor(0,0,0);
$pdf->SetXY(5, 68);
$pdf->Cell(44, 4, $emp['nombre'] . ' ' . $emp['apellido'], 0, 0, 'C');

// -------------------------------------------------
// Salida final del PDF
$pdf->Output('tarjeta_empleado_' . $empleado_id . '.pdf', 'I');
