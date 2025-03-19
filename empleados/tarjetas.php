<?php
session_start();
include '../auth.php';
include '../config.php';
requireRoles(['admin']);
if (!isset($_SESSION['user_id'])) {
  header("Location: " . BASE_URL . "login.php");
  exit();
}

$queryEmp = "SELECT id, nombre, apellido, puesto FROM empleados";
$resultEmp = $conexion->query($queryEmp);

include '../layout/header.php';
include '../layout/sidebar.php';
include '../layout/topbar.php';
?>

<!-- CSS de Tarjeta (si lo necesitas para estilizar el formulario) -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/empleados/tarjeta.css">

<div class="overlay"></div>
<div class="main-content panel-dark">
  <h2>Generar Tarjeta de Empleado</h2>
  <div class="tarjeta-container">
    <form action="generar_tarjeta.php" method="get" class="tarjeta-form">
      <div class="form-group">
        <label for="empleado_id">Empleado:</label>
        <select name="empleado_id" id="empleado_id" class="form-control" required>
          <option value="">Selecciona un empleado</option>
          <?php while ($row = $resultEmp->fetch_assoc()): ?>
            <option value="<?php echo $row['id']; ?>">
              <?php echo $row['nombre'] . " " . $row['apellido'] . " - " . $row['puesto']; ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="tarjeta-descripcion">
        <p>Al generar la tarjeta, se mostrará una página web con el diseño empresarial. 
        El frente incluirá nombre, foto (si existe) y logo de la empresa. 
        El reverso contendrá un texto sobre el uso de la tarjeta. 
        Podrás imprimirla directamente desde el navegador.</p>
      </div>
      <button type="submit" class="btn btn-primary">Generar Tarjeta</button>
    </form>
  </div>
</div>

<?php
include '../layout/footer.php';
?>

<!-- JS de Tarjeta (opcional) -->
<script src="<?php echo BASE_URL; ?>assets/js/empleados/tarjeta.js"></script>
