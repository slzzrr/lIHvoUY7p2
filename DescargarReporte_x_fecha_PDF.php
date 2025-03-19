<?php
error_reporting(E_ALL & ~E_DEPRECATED);
require_once('tcpdf/tcpdf.php');
include 'config.php';

// Obtener el reporte_id de la URL
$reporte_id = isset($_GET['reporte_id']) ? $_GET['reporte_id'] : 0;

// Obtener datos del reporte de la base de datos
$query = "SELECT * FROM reportes WHERE id = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $reporte_id);
$stmt->execute();
$result = $stmt->get_result();
$reporte = $result->fetch_assoc();

if (!$reporte) {
    die("Error: Reporte no encontrado.");
}

// Obtener datos de productos asociados al reporte
$query = "SELECT * FROM productos WHERE reporte_id = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $reporte_id);
$stmt->execute();
$result = $stmt->get_result();
$productos = [];
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

// Clase para generar el PDF
class MYPDF extends TCPDF {
    public $fechaCarga;
    public $fechaEntrega;
    public $folio;

    public function Header() {
        $logo = dirname(__FILE__) . '/assets/img/logpdf.png';
        $this->Image($logo, 10, 10, 50, 0, '', '', '', false, 300, '', false, false, 0);

        $this->AddBackgroundLogo();

        $this->SetFont('times', '', 14);
        $this->Cell(75, 91, '...juntos en cada ruta.', 0, 0, 'R');
        $this->SetFont('helvetica', '', 10);
        $this->SetXY(130, 1);
        $this->Cell(30, 60, "Flor de Nochebuena Lote 8,", 0, 'L', false);
        $this->SetXY(130, 1);
        $this->Cell(30, 67, "Col San Antonio Abad", 0, 'L', false);
        $this->SetXY(130, 1);
        $this->Cell(30, 74, "Toluca, México CP. 50245", 0, 'L', false);
        $this->SetLineWidth(0.5);
        $this->SetLineWidth(0.5);
        $this->SetLineWidth(0.5);
        $this->SetXY(180, 35); // Ajustar la posición para estar más a la derecha y más abajo
        $this->Line(180, 40, 130, 40); // Coordenadas ajustadas para hacer la línea más corta y a la derecha
        $this->Ln(10);
        $this->SetFont('helvetica', '', 10);
        $this->SetXY(120, 15);
        $this->Cell(146.2, 10, 'Fecha de Carga:', 0, 0, 'R');
        $this->Cell(50, 10, $this->fechaCarga, 0, 1, 'L');
        $this->SetXY(120, 20);
        $this->Cell(149, 10, 'Fecha de Entrega:', 0, 0, 'R');
        $this->Cell(50, 10, $this->fechaEntrega, 0, 1, 'L');
        $this->SetXY(120, 25);
        $this->Cell(147, 10, 'Hora de Entrega:', 0, 0, 'R');
        $this->Cell(50, 10, '', 0, 1, 'L'); // Dejar vacío
        $this->SetFont('helvetica', 'B', 12);
        $this->SetXY(150, 30);
        $this->Cell(197, 10, 'Folio: ' . $this->folio, 0, 1, 'C');
        $this->SetLineWidth(0.5);
        $this->Line(10, 50, 287, 50);
        $this->Ln(10);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

    // Método para agregar el logo de fondo
    public function AddBackgroundLogo() {
        $logo = dirname(__FILE__) . '/assets/img/logpdf.png';
        $this->SetAlpha(0.2); // Transparencia del fondo
        $this->Image($logo, 50, 50, 200, 0, '', '', '', false, 300, '', false, false, 0, false, false, true);
        $this->SetAlpha(1); // Restaurar la opacidad normal
    }

    public function createTable($header, $data) {
        $this->SetFont('helvetica', 'B', 10);
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.3);

        $num_headers = count($header);
        $w = array_fill(0, $num_headers, 38); // Ancho de las columnas
        foreach($header as $i => $header_item) {
            $this->Cell($w[$i], 7, $header_item, 1, 0, 'C', 1);
        }
        $this->Ln();

        $this->SetFont('helvetica', '', 10);
        foreach($data as $row) {
            foreach($row as $cell) {
                $this->Cell($w[$i], 6, $cell, 1);
            }
            $this->Ln();
        }
        $this->Ln(10); // Espacio después de la tabla
    }
}

// Crear instancia de la clase MYPDF
$pdf = new MYPDF('L', 'mm', 'A4');

// Asignar los datos recibidos a las propiedades de la clase MYPDF
$pdf->fechaCarga = date('d/m/Y', strtotime($reporte['fecha_carga'])); // Convertir la fecha a formato d-m-Y
$pdf->fechaEntrega = date('d/m/Y', strtotime($reporte['fecha_entrega'])); // Convertir la fecha a formato d-m-Y
$pdf->folio = $reporte['folio'];

// Añadir página al PDF
$pdf->AddPage();


// Datos del destinatario y remitente
$pdf->SetXY(10, 55);
$anchoCelda = 100;
$altoCelda = 7;

// Celda para el destinatario
$pdf->SetFont('helvetica', 'B', 10);
$pdf->MultiCell($anchoCelda, $altoCelda, 'Datos del Destinatario', 1, 'L', false, 1, '', '', true, 0, false, true, $altoCelda, 'M');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetX(10);
$nombre_destinatario = $reporte['nombre_destinatario'] == '0' ? '' : $reporte['nombre_destinatario'];
$direccion_destinatario = $reporte['direccion_destinatario'] == '0' ? '' : $reporte['direccion_destinatario'];
$pdf->MultiCell($anchoCelda, $altoCelda, 'Nombre: ' . $nombre_destinatario . "\n" . 'Dirección: ' . $direccion_destinatario, 1, 'L', false);

// Celda para el remitente
$pdf->SetXY(120, 55);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->MultiCell($anchoCelda, $altoCelda, 'Datos del Remitente', 1, 'L', false, 1, '', '', true, 0, false, true, $altoCelda, 'M');
$pdf->SetXY(120, 62);
$pdf->SetFont('helvetica', '', 10);
$nombre_remitente = $reporte['nombre_remitente'] == '0' ? '' : $reporte['nombre_remitente'];
$direccion_remitente = $reporte['direccion_remitente'] == '0' ? '' : $reporte['direccion_remitente'];
$pdf->MultiCell($anchoCelda, $altoCelda, 'Nombre: ' . $nombre_remitente . "\n" . 'Dirección: ' . $direccion_remitente, 1, 'L', false);

$pdf->Ln(); // Salto de línea después de las tablas

// NP, Descripción, Cantidad, SO, RMA
$pdf->SetX(10);
$header = ['NP', 'Descripción', 'Cantidad', 'SO', 'RMA'];

// Definir anchos fijos para las columnas
$npWidth = 25;           // Ancho fijo para "NP"
$cantidadWidth = 15;      // Ancho más pequeño para "Cantidad"
$soWidth = 20;            // Ancho fijo para "SO"
$rmaWidth = 20;           // Ancho fijo para "RMA"
$descripcionWidth = 60;   // Ancho fijo para "Descripción"

// Añadir el encabezado de la tabla
foreach ($header as $col) {
    if ($col == 'NP') {
        $pdf->Cell($npWidth, 10, $col, 1, 0, 'C');
    } elseif ($col == 'Descripción') {
        $pdf->Cell($descripcionWidth, 10, $col, 1, 0, 'C');
    } elseif ($col == 'Cantidad') {
        $pdf->Cell($cantidadWidth, 10, $col, 1, 0, 'C');
    } elseif ($col == 'SO') {
        $pdf->Cell($soWidth, 10, $col, 1, 0, 'C');
    } elseif ($col == 'RMA') {
        $pdf->Cell($rmaWidth, 10, $col, 1, 0, 'C');
    }
}
$pdf->Ln();

// Añadir los datos de los productos
foreach ($productos as $producto) {
    // Texto de la descripción
    $descripcionText = $producto['descripcion'];

    // Calcular la altura dinámica de "Descripción" basado en el contenido
    $descripcionHeight = $pdf->getStringHeight($descripcionWidth, $descripcionText);

    // Obtener la altura máxima de la fila
    $rowHeight = max($descripcionHeight, 10); // Al menos 10 unidades de alto

    // Dibujar las celdas con los anchos y alturas especificados
    $pdf->Cell($npWidth, $rowHeight, $producto['np'], 1, 0, 'C');
    $pdf->MultiCell($descripcionWidth, 10, $descripcionText, 1, 'L', 0, 0); // MultiCell para "Descripción" con ajuste de línea
    $pdf->Cell($cantidadWidth, $rowHeight, $producto['cantidad'], 1, 0, 'C');
    $pdf->Cell($soWidth, $rowHeight, $producto['so'], 1, 0, 'C');
    $pdf->Cell($rmaWidth, $rowHeight, $producto['rma'], 1, 1, 'C'); // Cambia el último parámetro a 1 para salto de línea
}


// Nombre de quien recibe, Compañía, Hora de recepción
$pdf->SetX(10);
$pdf->createTable(
    ['Quien recibe?', 'Compañía', 'Hora de recepción'],
    [
        [$reporte['nombre_recibe'], '', ''] // Dejar vacío
    ]
);

// Tabla combinada: Nombre del Operador, Placas de la Unidad y Observaciones
$pdf->SetX(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetTextColor(0);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.3);

// Encabezados de las columnas
$pdf->Cell(60, 7, 'Nombre del Operador', 1, 0, 'C', 1);
$pdf->Cell(60, 7, 'Placas de la Unidad', 1, 0, 'C', 1);
$pdf->Cell(80, 7, 'Observaciones', 1, 1, 'C', 1);

// Contenido de la tabla para el operador
$pdf->SetFont('helvetica', '', 10);
$nombre_operador = $reporte['nombre_operador'] == '0' ? '' : $reporte['nombre_operador'];
$placas_unidad = $reporte['placas_unidad'] == '0' ? '' : $reporte['placas_unidad'];
$observaciones = $reporte['observaciones'] == '0' ? '' : $reporte['observaciones'];
$pdf->Cell(60, 7, $nombre_operador, 1, 0, 'C');
$pdf->Cell(60, 7, $placas_unidad, 1, 0, 'C');
$pdf->Cell(80, 7, $observaciones, 1, 1, 'L');

// Tabla combinada: Recibo Completo, Recibo sin Daños y Observaciones del Recibo
$pdf->SetX(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetTextColor(0);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.3);

// Encabezados de las columnas
$pdf->Cell(60, 7, '', 1, 0, 'C', 1); // Espacio para alinear con la tabla anterior
$pdf->Cell(38, 7, 'Recibo Completo', 1, 0, 'C', 1);
$pdf->Cell(38, 7, 'Recibo sin Daños', 1, 0, 'C', 1);
$pdf->Cell(70, 7, 'Observaciones del Recibo', 1, 1, 'C', 1);

// Contenido de la tabla
$pdf->Cell(60, 7, 'Sí', 1, 0, 'C');
$pdf->Cell(38, 7, $reporte['recibo_completo'] ? 'Sí' : '', 1, 0, 'C');
$pdf->Cell(38, 7, $reporte['recibo_sin_danos'] ? 'Sí' : '', 1, 0, 'C');
$pdf->Cell(70, 7, '', 1, 1, 'L'); // Dejar vacío

$pdf->Cell(60, 7, 'No', 1, 0, 'C');
$pdf->Cell(38, 7, $reporte['recibo_completo'] ? 'No' : '', 1, 0, 'C');
$pdf->Cell(38, 7, $reporte['recibo_sin_danos'] ? 'No' : '', 1, 0, 'C');
$pdf->Cell(70, 7, '', 1, 1, 'C'); // Dejar vacío para alinear con la columna de observaciones

// Definir la carpeta y el nombre del archivo
$folder = __DIR__ . '/saves/';
$file_name = $folder . 'reporte_' . $reporte_id . '.pdf';

// Asegúrate de que la carpeta "saves" exista y tenga permisos de escritura
if (!file_exists($folder)) {
    mkdir($folder, 0777, true);
}
if (!is_writable($folder)) {
    die("Error: La carpeta 'saves' no tiene permisos de escritura.");
}

// Guardar el archivo PDF en la carpeta "saves"
$pdf->Output($file_name, 'F');

// Guardar la ruta del PDF en la base de datos
$query = "UPDATE reportes SET ruta_pdf = ? WHERE id = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("si", $file_name, $reporte_id);
$stmt->execute();

// Mostrar el PDF al usuario para que pueda verlo e imprimirlo
$pdf->Output('reporte_' . $reporte_id . '.pdf', 'I');
?>