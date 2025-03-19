<?php
session_start();
include '../auth.php';
include '../config.php';
requireRoles(['admin']);
if (!isset($_SESSION['user_id'])) {
  header("Location: " . BASE_URL . "login.php");
  exit();
}

$mensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $empleado_id = $_POST['empleado_id'];
  $stmt = $conexion->prepare("SELECT nombre, apellido, email FROM empleados WHERE id = ?");
  $stmt->bind_param("i", $empleado_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $emp = $res->fetch_assoc();
  $stmt->close();

  if ($emp) {
    $baseEmail = strtolower($emp['nombre'] . "." . $emp['apellido']);
    $domain = "fersus.com.mx";
    $correo = $baseEmail . "@" . $domain;

    // Podrías verificar duplicados
    $stmtUp = $conexion->prepare("UPDATE empleados SET email = ? WHERE id = ?");
    $stmtUp->bind_param("si", $correo, $empleado_id);
    $stmtUp->execute();
    $stmtUp->close();

    $mensaje = "Correo generado: " . $correo;
  } else {
    $mensaje = "Empleado no encontrado.";
  }
}

$queryEmp = "SELECT id, nombre, apellido, email FROM empleados";
$resultEmp = $conexion->query($queryEmp);

include '../layout/header.php';
include '../layout/sidebar.php';
include '../layout/topbar.php';
?>

<!-- CSS de Correo -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/empleados/correo.css">

<div class="overlay"></div>
<div class="main-content panel-dark">
  <h2>Generar Correo de Empleado</h2>

  <?php if ($mensaje): ?>
    <p class="notification" style="display:block;"><?php echo $mensaje; ?></p>
  <?php endif; ?>

  <div class="correo-container">
    <h3>Seleccionar Empleado</h3>
    <form action="correo.php" method="post" class="correo-form">
      <div class="form-group">
        <label for="empleado_id">Empleado:</label>
        <select name="empleado_id" id="empleado_id" class="form-control" required>
          <option value="">Selecciona un empleado</option>
          <?php while ($row = $resultEmp->fetch_assoc()): ?>
            <option value="<?php echo $row['id']; ?>">
              <?php echo $row['nombre'] . " " . $row['apellido']; ?>
              <?php echo $row['email'] ? " (Actual: ".$row['email'].")" : ""; ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Generar Correo</button>
    </form>
  </div>
</div>

<?php
include '../layout/footer.php';
?>

<!-- JS para Correo -->
<
