<?php
session_start();

// 1) Incluir archivos necesarios
include '../auth.php';
include '../config.php';
requireRoles(['admin']); // Solo admin

// 2) Obtener ID del salario
$salario_id = $_GET['id'] ?? 0;
if (!$salario_id) {
    die("ID de salario no especificado.");
}

// 3) Consultar la BD para obtener info del salario y colaborador
$sql = "
  SELECT s.id, s.salario, s.periodicidad, s.empleado_id, s.fecha_registro,
         s.deuda_monto, s.deuda_periodos_restantes, s.deuda_motivo, s.deuda_descuento,
         s.salario_durante_deuda, s.fecha_inicio_deuda, s.fecha_fin_deuda,
         e.nombre, e.apellido
  FROM salarios s
  JOIN empleados e ON e.id = s.empleado_id
  WHERE s.id = ?
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $salario_id);
$stmt->execute();
$result = $stmt->get_result();
$salData = $result->fetch_assoc();
$stmt->close();

if (!$salData) {
    die("No se encontró el registro de salario con ID: $salario_id");
}

// Variables para mensajes
$errors = [];
$success = "";
$pdf_generated_path = null; // para mostrar el link al PDF

// 4) Ver qué formulario se envió (Incrementar o Deuda)
$accion = $_POST['accion'] ?? '';

// --------------------------------------------------------------------
// A) LÓGICA PARA "INCREMENTAR SALARIO"
// --------------------------------------------------------------------
if ($accion === 'incremento') {
    // 1) Recibir campos
    $incremento_por = $_POST['incremento_por'] ?? '0';

    // 2) Validar
    if (!is_numeric($incremento_por)) {
        $errors[] = "Porcentaje de incremento inválido.";
    }

    // 3) Si no hay errores, proceder
    if (empty($errors)) {
        $incremento_por = floatval($incremento_por);
        $salario_base   = floatval($salData['salario']);

        // Aplicar incremento
        if ($incremento_por > 0) {
            $monto_incremento = $salario_base * ($incremento_por / 100.0);
            $salario_base += $monto_incremento;
        }

        // Actualizar en la BD
        $updSql = "UPDATE salarios SET salario=? WHERE id=?";
        $stmt2 = $conexion->prepare($updSql);
        $stmt2->bind_param("di", $salario_base, $salario_id);
        $stmt2->execute();
        $stmt2->close();

        // Refrescar salData
        $salData['salario'] = $salario_base;

        $success = "Salario incrementado correctamente.";
    }
}

// --------------------------------------------------------------------
// B) LÓGICA PARA "REGISTRAR O ACTUALIZAR DEUDA"
// --------------------------------------------------------------------
elseif ($accion === 'deuda') {
    // 1) Recibir campos
    $monto_deuda     = $_POST['monto_deuda']     ?? '0';
    $motivo_deuda    = $_POST['motivo_deuda']    ?? '';
    $num_periodos    = $_POST['num_periodos']    ?? '0';
    $fecha_inicio    = $_POST['fecha_inicio']    ?? '';
    // Ajusta si deseas capturar la fecha fin
    // o la calculamos en base a la periodicidad
    // Por simplicidad, supongamos la ingresa manual o la calculas a posteriori

    // 2) Validar
    if (!is_numeric($monto_deuda) || floatval($monto_deuda) <= 0) {
        $errors[] = "Monto de deuda inválido.";
    }
    if (empty($motivo_deuda)) {
        $errors[] = "Motivo de la deuda es requerido.";
    }
    if (!is_numeric($num_periodos) || intval($num_periodos) < 1) {
        $errors[] = "Número de periodos inválido.";
    }
    if (empty($fecha_inicio)) {
        $errors[] = "Fecha de inicio de deuda requerida.";
    }

    if (empty($errors)) {
        $monto_deuda  = floatval($monto_deuda);
        $num_periodos = intval($num_periodos);

        // Calcular el descuento por periodo
        $descuento = $monto_deuda / $num_periodos;

        // Calcular "salario_durante_deuda" => 
        // = salario base - descuento
        // (Asumiendo que la retención se descuenta COMPLETA en cada periodo)
        $salario_base = floatval($salData['salario']);
        $nuevo_salario = $salario_base - $descuento;
        if ($nuevo_salario < 0) {
            $nuevo_salario = 0; // Evitar valores negativos
        }

        // (Opcional) fecha_fin, si deseas calcularla
        // Ejemplo: si la periodicidad es quincenal => + (num_periodos * 2) weeks
        // o algo similar
        $fecha_fin = null;
        // Ajusta a tu gusto
        // $fecha_fin = date('Y-m-d', strtotime("+".($num_periodos*2)." weeks"));

        // Actualizar la tabla salarios con la deuda
        $updDeuda = "
          UPDATE salarios
          SET deuda_monto=?, 
              deuda_periodos_restantes=?,
              deuda_motivo=?,
              deuda_descuento=?,
              salario_durante_deuda=?,
              fecha_inicio_deuda=?,
              fecha_fin_deuda=?
          WHERE id=?
        ";
        $stmt3 = $conexion->prepare($updDeuda);
        $stmt3->bind_param(
            "disdissi",
            $monto_deuda,
            $num_periodos,
            $motivo_deuda,
            $descuento,
            $nuevo_salario,
            $fecha_inicio,
            $fecha_fin,
            $salario_id
        );
        $stmt3->execute();
        $stmt3->close();

        // Refrescar salData
        $salData['deuda_monto']            = $monto_deuda;
        $salData['deuda_periodos_restantes'] = $num_periodos;
        $salData['deuda_motivo']           = $motivo_deuda;
        $salData['deuda_descuento']        = $descuento;
        $salData['salario_durante_deuda']  = $nuevo_salario;
        $salData['fecha_inicio_deuda']     = $fecha_inicio;
        $salData['fecha_fin_deuda']        = $fecha_fin;

        // Generar PDF con TCPDF
        require_once('../tcpdf/tcpdf.php');
        $pdf = new TCPDF('P','mm','LETTER', true, 'UTF-8', false);
        $pdf->SetCreator('Sistema');
        $pdf->SetAuthor('Fersus');
        $pdf->SetTitle('Registro de Deuda');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        // Logo
        $logoPath = __DIR__ . '/../assets/img/logo.png';
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 15, 10, 40, 0, '', '', '', false, 300);
        }
        $pdf->SetFont('helvetica','B',14);
        $pdf->Cell(0, 10, "Registro de Deuda - Fersus", 0, 1, 'C');
        $pdf->Ln(20);

        // Info
        $pdf->SetFont('helvetica','',11);
        $pdf->MultiCell(0, 6, "Colaborador: " . $salData['nombre'] . " " . $salData['apellido'], 0, 'L');
        $pdf->MultiCell(0, 6, "Salario ID: " . $salData['id'], 0, 'L');
        $pdf->Ln(5);

        $pdf->MultiCell(0, 6, "Monto Deuda: $" . number_format($monto_deuda, 2), 0, 'L');
        $pdf->MultiCell(0, 6, "Motivo: " . $motivo_deuda, 0, 'L');
        $pdf->MultiCell(0, 6, "Número de Periodos: $num_periodos", 0, 'L');
        $pdf->MultiCell(0, 6, "Descuento por periodo: $" . number_format($descuento, 2), 0, 'L');
        $pdf->MultiCell(0, 6, "Fecha Inicio: " . $fecha_inicio, 0, 'L');
        // $pdf->MultiCell(0, 6, "Fecha Fin: " . $fecha_fin, 0, 'L'); // si la calculas
        $pdf->Ln(5);

        // Saldo posterior
        $pdf->MultiCell(0, 6, "Salario base anterior: $" . number_format($salario_base, 2), 0, 'L');
        $pdf->MultiCell(0, 6, "Saldo posterior (durante deuda): $" . number_format($nuevo_salario, 2), 0, 'L');

        // Espacio firma
        $pdf->Ln(15);
        $pdf->MultiCell(0, 6, "____________________________________", 0, 'C');
        $pdf->MultiCell(0, 6, "Firma del Colaborador", 0, 'C');

        // Asegurar carpeta _retenciones
        $pdfDir = __DIR__ . '/_retenciones/';
        if (!file_exists($pdfDir)) {
            mkdir($pdfDir, 0777, true);
        }
        // Nombre PDF
        $pdf_filename = 'deuda_salario_'.$salario_id.'_'.date('YmdHis').'.pdf';
        $pdf_path = $pdfDir . $pdf_filename;

        // Guardar PDF
        $pdf->Output($pdf_path, 'F');

        $pdf_generated_path = $pdf_filename;
        $success = "Deuda registrada/actualizada correctamente. PDF: $pdf_filename";
    }
}

// Incluir layout
include '../layout/header.php';
include '../layout/sidebar.php';
include '../layout/topbar.php';
?>

<style>
.container-edit {
  max-width: 700px;
  margin: 20px auto;
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  color: #000; /* Fuerza el texto a negro */
}

label {
  display: block;
  margin-top: 10px;
  font-weight: bold;
  color: #000;
}

input, select, textarea {
  width: 100%;
  padding: 8px;
  margin-top: 5px;
  box-sizing: border-box;
  color: #000;
  border: 1px solid #ccc;
}

.btn {
  padding: 8px 15px;
  background: #0066cc;
  color: #fff; /* Botón mantiene texto blanco */
  border: none;
  border-radius: 4px;
  margin-top: 15px;
  cursor: pointer;
}

.alert {
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 15px;
  color: #000;
}

.alert-error {
  background: #f8d7da;
  color: #721c24;
}

.alert-success {
  background: #d4edda;
  color: #155724;
}

.form-section {
  border: 1px solid #ccc;
  padding: 15px;
  border-radius: 6px;
  margin-top: 20px;
  color: #000;
}

</style>

<div class="overlay"></div>
<div class="main-content panel-dark">
  <div class="container-edit">
    <h2>Editar Salario (Tabla Única)</h2>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <?php foreach($errors as $e): ?>
          <p><?php echo htmlspecialchars($e); ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success">
        <?php echo htmlspecialchars($success); ?>
        <?php if ($pdf_generated_path): ?>
          <br><a href="_retenciones/<?php echo $pdf_generated_path; ?>" target="_blank">Ver PDF</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Info del colaborador y salario -->
    <p><strong>Colaborador:</strong> 
       <?php echo htmlspecialchars($salData['nombre'].' '.$salData['apellido']); ?></p>
    <p><strong>Salario Base:</strong> 
       $<?php echo number_format($salData['salario'],2); ?></p>
    <p><strong>Periodicidad:</strong> 
       <?php echo htmlspecialchars($salData['periodicidad']); ?></p>
    <p><strong>Deuda Monto:</strong> 
       $<?php echo number_format($salData['deuda_monto'],2); ?></p>
    <p><strong>Deuda Periodos Restantes:</strong> 
       <?php echo (int)$salData['deuda_periodos_restantes']; ?></p>
    <p><strong>Salario Durante Deuda:</strong> 
       $<?php echo number_format($salData['salario_durante_deuda'],2); ?></p>

    <!-- FORMULARIO 1: Incrementar Salario -->
    <div class="form-section">
      <h3>Incrementar Salario</h3>
      <form method="post" action="editar_salario.php?id=<?php echo $salario_id; ?>">
        <input type="hidden" name="accion" value="incremento">

        <label for="incremento_por">Incremento (%):</label>
        <input type="number" step="0.01" name="incremento_por" id="incremento_por" placeholder="0">

        <button type="submit" class="btn">Aplicar Aumento</button>
      </form>
    </div>

    <!-- FORMULARIO 2: Registrar/Actualizar Deuda -->
    <div class="form-section">
      <h3>Registrar o Actualizar Deuda</h3>
      <form method="post" action="editar_salario.php?id=<?php echo $salario_id; ?>">
        <input type="hidden" name="accion" value="deuda">

        <label for="monto_deuda">Monto de la Deuda:</label>
        <input type="number" step="0.01" name="monto_deuda" id="monto_deuda" placeholder="0.00">

        <label for="motivo_deuda">Motivo de la Deuda:</label>
        <textarea name="motivo_deuda" id="motivo_deuda" rows="3"></textarea>

        <label for="num_periodos">Número de Periodos:</label>
        <input type="number" name="num_periodos" id="num_periodos" placeholder="1">

        <label for="fecha_inicio">Fecha de Inicio Deuda:</label>
        <input type="date" name="fecha_inicio" id="fecha_inicio">

        <button type="submit" class="btn">Registrar Deuda</button>
      </form>
    </div>

    <p style="margin-top:20px; color:#666;">
      <small>
        ID Salario: <?php echo $salData['id']; ?> |
        Fecha Registro: <?php echo $salData['fecha_registro']; ?>
      </small>
    </p>
  </div>
</div>

<?php include '../layout/footer.php'; ?>