<?php
session_start();
include '../auth.php';
include '../config.php';
requireRoles(['admin']);

if (!isset($_SESSION['user_id'])) {
  header("Location: ".BASE_URL."login.php");
  exit();
}

$mensaje = null;

// Si envían formulario:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empleado_id  = $_POST['empleado_id'] ?? null;
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin    = $_POST['fecha_fin'] ?? null;
    $motivo       = $_POST['motivo'] ?? '';
    $goce_sueldo  = isset($_POST['goce_sueldo']) ? 1 : 0;

    if (!$empleado_id || !$fecha_inicio || !$fecha_fin) {
        $mensaje = "Todos los campos obligatorios deben completarse.";
    } else {
        // 1) Revisar superposición
        $sqlOverlap = "
          SELECT COUNT(*) AS total
          FROM vacaciones
          WHERE empleado_id = ?
            AND (fecha_inicio <= ? AND fecha_fin >= ?)
        ";
        $stmtO = $conexion->prepare($sqlOverlap);
        $stmtO->bind_param("iss", $empleado_id, $fecha_fin, $fecha_inicio);
        $stmtO->execute();
        $resO = $stmtO->get_result();
        $rowO = $resO->fetch_assoc();
        $stmtO->close();

        if ($rowO['total'] > 0) {
            $mensaje = "El empleado ya tiene vacaciones activas que se traslapan con estas fechas.";
        } else {
            // 2) Revisar días disponibles
            // Supongamos que cada empleado tiene 10 días anuales
            // (O lo consultas de la tabla empleados si lo manejas de forma dinámica)
            $diasAnuales = 10;

            $diasSolicitados = (strtotime($fecha_fin) - strtotime($fecha_inicio))/86400 + 1;
            $añoVac = date('Y', strtotime($fecha_inicio));

            $sqlDiasUsados = "
              SELECT SUM(DATEDIFF(fecha_fin, fecha_inicio) + 1) AS dias_usados
              FROM vacaciones
              WHERE empleado_id = ?
                AND YEAR(fecha_inicio) = ?
                AND YEAR(fecha_fin)   = ?
            ";
            $stmtD = $conexion->prepare($sqlDiasUsados);
            $stmtD->bind_param("iii", $empleado_id, $añoVac, $añoVac);
            $stmtD->execute();
            $resD = $stmtD->get_result();
            $rowD = $resD->fetch_assoc();
            $diasUsados = (int)$rowD['dias_usados'];
            $stmtD->close();

            $diasDisponibles = $diasAnuales - $diasUsados;

            if ($diasSolicitados > $diasDisponibles) {
                $mensaje = "No tiene suficientes días de vacaciones. Solo le quedan $diasDisponibles días.";
            } else {
                // 3) Insertar si todo ok
                $stmtVac = $conexion->prepare("
                    INSERT INTO vacaciones 
                    (empleado_id, fecha_inicio, fecha_fin, motivo, goce_sueldo) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtVac->bind_param("isssi", $empleado_id, $fecha_inicio, $fecha_fin, $motivo, $goce_sueldo);
                $stmtVac->execute();
                $stmtVac->close();
                $mensaje = "Vacaciones registradas correctamente.";
            }
        }
    }
}

// Para mostrar lista de empleados
$queryEmp = "SELECT id, nombre, apellido, puesto FROM empleados ORDER BY nombre ASC";
$resEmp = $conexion->query($queryEmp);

// Quiénes están de vacaciones hoy
$queryVacacionesActuales = "
    SELECT v.id, e.nombre, e.apellido, v.fecha_inicio, v.fecha_fin,
           DATEDIFF(v.fecha_fin, CURDATE()) AS dias_restantes
    FROM vacaciones v
    JOIN empleados e ON v.empleado_id = e.id
    WHERE CURDATE() BETWEEN v.fecha_inicio AND v.fecha_fin
    ORDER BY v.fecha_fin ASC
";
$resVacAct = $conexion->query($queryVacacionesActuales);

include '../layout/header.php';
include '../layout/sidebar.php';
include '../layout/topbar.php';
?>

<!-- CSS de Vacaciones (puedes ajustar o mover a un archivo CSS) -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/empleados/vacaciones.css">

<div class="overlay"></div>
<div class="main-content panel-dark">
  <h2 style="color:#000;">Vacaciones</h2>

  <?php if ($mensaje): ?>
    <p class="notification" style="display:block; color:#000;"><?php echo htmlspecialchars($mensaje); ?></p>
  <?php endif; ?>

  <!-- Formulario para registrar vacaciones -->
  <div class="vacaciones-container">
    <h3 style="color:#000;">Registrar Vacaciones</h3>
    <form action="vacaciones.php" method="post" class="vacaciones-form">
      <div class="form-group">
        <label for="empleado_id" style="color:#000;">Empleado:</label>
        <select name="empleado_id" id="empleado_id" class="form-control" required>
          <option value="">Selecciona un empleado</option>
          <?php while ($row = $resEmp->fetch_assoc()): ?>
            <option value="<?php echo $row['id']; ?>">
              <?php echo htmlspecialchars($row['nombre'] . " " . $row['apellido'] . " - " . $row['puesto']); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="fecha_inicio" style="color:#000;">Fecha de Inicio:</label>
        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="fecha_fin" style="color:#000;">Fecha de Fin:</label>
        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="motivo" style="color:#000;">Motivo de Vacaciones:</label>
        <textarea name="motivo" id="motivo" class="form-control" rows="3"></textarea>
      </div>
      <div class="form-group">
        <label style="color:#000;">
          <input type="checkbox" name="goce_sueldo" id="goce_sueldo">
          Vacaciones con goce de sueldo
        </label>
      </div>
      <button type="submit" class="btn btn-primary">Registrar Vacaciones</button>
    </form>
  </div>

  <!-- Listado de empleados actualmente de vacaciones -->
  <div class="vacaciones-actuales">
    <h3 style="color:#000;">Empleados Actualmente de Vacaciones</h3>
    <?php if ($resVacAct && $resVacAct->num_rows > 0): ?>
      <table class="table" style="color:#000;">
        <thead>
          <tr>
            <th>Empleado</th>
            <th>Fecha Inicio</th>
            <th>Fecha Fin</th>
            <th>Días Restantes</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($vac = $resVacAct->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($vac['nombre'] . ' ' . $vac['apellido']); ?></td>
              <td><?php echo htmlspecialchars($vac['fecha_inicio']); ?></td>
              <td><?php echo htmlspecialchars($vac['fecha_fin']); ?></td>
              <td><?php echo htmlspecialchars($vac['dias_restantes']); ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p style="color:#000;">No hay empleados en vacaciones actualmente.</p>
    <?php endif; ?>
  </div>
</div>

<?php
include '../layout/footer.php';
?>

<!-- JS específico para Vacaciones -->
<script src="<?php echo BASE_URL; ?>assets/js/empleados/vacaciones.js"></script>
