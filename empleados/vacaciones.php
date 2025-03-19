<?php
session_start();
include '../auth.php';
include '../config.php';
requireRoles(['admin']);

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
  header("Location: " . BASE_URL . "login.php");
  exit();
}

$mensaje = null;

// Procesar el formulario de registro de vacaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $empleado_id  = $_POST['empleado_id'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin    = $_POST['fecha_fin'];
    $motivo       = $_POST['motivo'];
    $goce_sueldo  = isset($_POST['goce_sueldo']) ? 1 : 0;

    // Validar la antigüedad del empleado (ejemplo básico)
    $stmtEmp = $conexion->prepare("SELECT fecha_ingreso FROM empleados WHERE id = ?");
    $stmtEmp->bind_param("i", $empleado_id);
    $stmtEmp->execute();
    $resEmp = $stmtEmp->get_result();
    $emp = $resEmp->fetch_assoc();
    $stmtEmp->close();

    if ($emp) {
        // Calcular antigüedad en años
        $fi = new DateTime($emp['fecha_ingreso']);
        $hoy = new DateTime();
        $diff = $fi->diff($hoy);
        $antiguedad = $diff->y; // años de antigüedad

        if ($antiguedad < 1) {
            $mensaje = "El empleado no cumple la antigüedad mínima para vacaciones (1 año).";
        } else {
            // Registrar las vacaciones
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

// Para el formulario de registro de vacaciones, listamos empleados
$queryEmp = "SELECT id, nombre, apellido, puesto FROM empleados";
$resEmp = $conexion->query($queryEmp);

// Consulta para mostrar quiénes están de vacaciones actualmente
// y cuántos días les quedan
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

<!-- CSS de Vacaciones -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/empleados/vacaciones.css">

<div class="overlay"></div>
<div class="main-content panel-dark">
  <h2>Vacaciones</h2>

  <?php if ($mensaje): ?>
    <p class="notification" style="display:block;"><?php echo $mensaje; ?></p>
  <?php endif; ?>

  <!-- Formulario para registrar vacaciones -->
  <div class="vacaciones-container">
    <h3>Registrar Vacaciones</h3>
    <form action="vacaciones.php" method="post" class="vacaciones-form">
      <div class="form-group">
        <label for="empleado_id">Empleado:</label>
        <select name="empleado_id" id="empleado_id" class="form-control" required>
          <option value="">Selecciona un empleado</option>
          <?php while ($row = $resEmp->fetch_assoc()): ?>
            <option value="<?php echo $row['id']; ?>">
              <?php echo $row['nombre'] . " " . $row['apellido'] . " - " . $row['puesto']; ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="fecha_inicio">Fecha de Inicio:</label>
        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="fecha_fin">Fecha de Fin:</label>
        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="motivo">Motivo de Vacaciones:</label>
        <textarea name="motivo" id="motivo" class="form-control" rows="3"></textarea>
      </div>
      <div class="form-group">
        <label>
          <input type="checkbox" name="goce_sueldo" id="goce_sueldo">
          Vacaciones con goce de sueldo
        </label>
      </div>
      <button type="submit" class="btn btn-primary">Registrar Vacaciones</button>
    </form>
  </div>

  <!-- Listado de empleados actualmente de vacaciones -->
  <div class="vacaciones-actuales">
    <h3>Empleados Actualmente de Vacaciones</h3>

    <?php if ($resVacAct->num_rows > 0): ?>
      <table class="table">
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
              <td><?php echo $vac['nombre'] . ' ' . $vac['apellido']; ?></td>
              <td><?php echo $vac['fecha_inicio']; ?></td>
              <td><?php echo $vac['fecha_fin']; ?></td>
              <td><?php echo $vac['dias_restantes']; ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No hay empleados en vacaciones actualmente.</p>
    <?php endif; ?>
  </div>
</div>

<?php
include '../layout/footer.php';
?>

<!-- JS específico para Vacaciones -->
<script src="<?php echo BASE_URL; ?>assets/js/empleados/vacaciones.js"></script>
