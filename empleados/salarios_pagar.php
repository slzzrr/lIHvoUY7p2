<?php
session_start();
include '../auth.php';
include '../config.php';
requireRoles(['admin']); // Solo admin puede ver esta página

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

function calcularFechasPago($ultimaFechaPago, $periodicidad, $fechaRegistro) {
    $baseDateStr = $ultimaFechaPago ?: $fechaRegistro;
    if (!$baseDateStr) {
        return ['ultimoPago' => null, 'proximoPago' => null];
    }
    // Usamos solo la parte de fecha (si es DATETIME)
    $fechaBase = new DateTime(substr($baseDateStr, 0, 10));
    $hoy = new DateTime(date('Y-m-d'));
    $ultimoPago = clone $fechaBase;
    $fechaIter  = clone $fechaBase;
    
    while ($fechaIter <= $hoy) {
        $ultimoPago = clone $fechaIter;
        switch ($periodicidad) {
            case 'mensual':
                $fechaIter->modify('+1 month');
                break;
            case 'quincenal':
                $fechaIter->modify('+15 days');
                break;
            case 'semanal':
                $fechaIter->modify('+7 days');
                break;
            case 'diario':
                $fechaIter->modify('+1 day');
                break;
            default:
                return ['ultimoPago' => null, 'proximoPago' => null];
        }
    }
    
    return [
        'ultimoPago'  => $ultimoPago->format('Y-m-d'),
        'proximoPago' => $fechaIter->format('Y-m-d')
    ];
}

// Si se marca un pago como realizado, actualizamos la última fecha de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salary_id'])) {
    $salaryId = intval($_POST['salary_id']);
    $upd = "UPDATE salarios SET ultima_fecha_pago = CURDATE() WHERE id = ?";
    $stmt = $conexion->prepare($upd);
    $stmt->bind_param("i", $salaryId);
    if ($stmt->execute()) {
        $successMessage = "Salario #$salaryId marcado como pagado (ultima_fecha_pago = HOY).";
    } else {
        $errorMessage = "Error al actualizar salario #$salaryId.";
    }
    $stmt->close();
}

// Consultar todos los salarios y datos de empleados
$sql = "
    SELECT 
      s.id AS salary_id,
      s.empleado_id,
      s.salario,
      s.periodicidad,
      s.fecha_registro,
      s.ultima_fecha_pago,
      e.nombre,
      e.apellido,
      e.email,
      e.telefono
    FROM salarios s
    JOIN empleados e ON e.id = s.empleado_id
    ORDER BY s.id DESC
";
$res = $conexion->query($sql);

$today = date('Y-m-d');
$dueSalaries = [];
while ($row = $res->fetch_assoc()) {
    $fechas = calcularFechasPago(
        $row['ultima_fecha_pago'],
        $row['periodicidad'],
        $row['fecha_registro']
    );
    $ultimoPago  = $fechas['ultimoPago'];
    $proximoPago = $fechas['proximoPago'];
    
    // Si el próximo pago es hoy, agregar al listado
    if ($proximoPago === $today) {
        $row['proximoPago'] = $proximoPago;
        $row['ultimoPago']  = $ultimoPago;
        $dueSalaries[] = $row;
    }
}

// ----------------------------------------------------------------------
// Enviar notificación por correo usando PHPMailer a cada empleado con correo
// ----------------------------------------------------------------------

// Cargar PHPMailer (asegúrate de que el autoload de Composer está en la ruta correcta)
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (count($dueSalaries) > 0) {
    foreach ($dueSalaries as $sal) {
        // Solo enviar si el empleado tiene correo registrado
        if (!empty($sal['email'])) {
            $mail = new PHPMailer(true);
            try {
                // Configuración del servidor (SMTP)
                $mail->isSMTP();
                $mail->Host       = 'smtp.hostinger.com';    // Cambia al host de tu servidor SMTP
                $mail->SMTPAuth   = true;
                $mail->Username   = 'pagos@salan.group';  // Tu usuario SMTP
                $mail->Password   = 'Cheater099?!';             // Tu contraseña SMTP
                $mail->SMTPSecure = 'ssl';
                $mail->Port       = 465;

                // Remitente y destinatario
                $mail->setFrom('tu_correo@tuservidor.com', 'Fersus Transportes');
                $mail->addAddress($sal['email'], $sal['nombre'] . ' ' . $sal['apellido']);

                // Contenido del correo
                $mail->isHTML(true);
                $mail->Subject = "Aviso de pago de salario - Fersus Transportes";
                $mail->Body    = "
                    <p>Hola {$sal['nombre']} {$sal['apellido']},</p>
                    <p>Te notificamos que tu salario (ID: {$sal['salary_id']}) debe ser pagado hoy ({$today}).</p>
                    <p>
                      Monto: $" . number_format($sal['salario'], 2) . "<br>
                      Periodicidad: " . htmlspecialchars($sal['periodicidad']) . "
                    </p>
                    <p>Por favor, verifica que se realice el pago a la brevedad.</p>
                    <p>Saludos,<br>Fersus Transportes</p>
                ";
                $mail->AltBody = "Hola {$sal['nombre']} {$sal['apellido']}, tu salario (ID: {$sal['salary_id']}) debe ser pagado hoy ({$today}). Monto: $" . number_format($sal['salario'], 2) . ", Periodicidad: {$sal['periodicidad']}. Saludos, Fersus Transportes.";

                $mail->send();
                // Podrías guardar un log de envío si lo deseas
            } catch (Exception $e) {
                // Manejar error (por ejemplo, registrar en log)
                // echo "Error al enviar a {$sal['email']}: {$mail->ErrorInfo}";
            }
        }
    }
}

// Incluir layout (header, sidebar, topbar)
include '../layout/header.php';
include '../layout/sidebar.php';
include '../layout/topbar.php';
?>

<!-- Estilos personalizados -->
<style>
.container-dashboard {
  max-width: 1100px;
  margin: 20px auto;
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  color: #000;
}
.container-dashboard h1 {
  text-align: center;
  margin-bottom: 20px;
  color: #000;
}
.alert {
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 15px;
}
.alert-success {
  background: #d4edda;
  color: #155724;
}
.alert-error {
  background: #f8d7da;
  color: #721c24;
}
.table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
  background-color: #fff;
}
.table th, .table td {
  border: 1px solid #ccc;
  padding: 10px;
  text-align: center;
  color: #000;
}
.table th.section-title {
  background-color: #333;
  color: #fff;
  font-size: 1.1em;
  font-weight: bold;
  padding: 12px;
}
.btn {
  padding: 6px 12px;
  background: #0066cc;
  color: #fff;
  border: none;
  border-radius: 4px;
  text-decoration: none;
  cursor: pointer;
}
</style>

<div class="overlay"></div>
<div class="main-content panel-dark">
  <div class="container-dashboard">
    <h1>Salarios a Pagar Hoy (<?php echo $today; ?>)</h1>

    <?php if (isset($successMessage)): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php elseif (isset($errorMessage)): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <?php if (count($dueSalaries) > 0): ?>
      <table class="table">
        <thead>
          <tr>
            <th class="section-title" colspan="9">Listado de Salarios Pendientes de Pago</th>
          </tr>
          <tr>
            <th>ID Salario</th>
            <th>Colaborador</th>
            <th>Salario (MXN)</th>
            <th>Periodicidad</th>
            <th>Último Pago</th>
            <th>Próximo Pago</th>
            <th>Fecha Registro</th>
            <th>Teléfono</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($dueSalaries as $sal): ?>
            <tr>
              <td><?php echo $sal['salary_id']; ?></td>
              <td><?php echo htmlspecialchars($sal['nombre'] . ' ' . $sal['apellido']); ?></td>
              <td><?php echo '$' . number_format($sal['salario'], 2); ?></td>
              <td><?php echo htmlspecialchars($sal['periodicidad']); ?></td>
              <td><?php echo $sal['ultimoPago'] ?: 'N/A'; ?></td>
              <td><?php echo $sal['proximoPago'] ?: 'N/A'; ?></td>
              <td><?php echo substr($sal['fecha_registro'], 0, 10); ?></td>
              <td><?php echo htmlspecialchars($sal['telefono']); ?></td>
              <td>
                <!-- Botón para marcar como pagado -->
                <form method="post" action="salarios_pagar.php" style="margin:0;">
                  <input type="hidden" name="salary_id" value="<?php echo $sal['salary_id']; ?>">
                  <button type="submit" class="btn">Pagado</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No hay salarios a pagar hoy.</p>
    <?php endif; ?>
  </div>
</div>

<?php include '../layout/footer.php'; ?>