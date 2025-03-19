<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}

include 'config.php';

// Obtener estadísticas
$queryProductos = "SELECT COUNT(*) as total FROM productos";
$resProd = $conexion->query($queryProductos);
$totalProductos = $resProd->fetch_assoc()['total'] ?? 0;

$queryReportes = "SELECT COUNT(DISTINCT folio) as total FROM reportes";
$resReps = $conexion->query($queryReportes);
$totalReportes = $resReps->fetch_assoc()['total'] ?? 0;

// Incluir layout
include 'layout/header.php';
include 'layout/sidebar.php';
include 'layout/topbar.php';
?>

<!-- Overlay en el fondo -->
<div class="overlay"></div>

<!-- Contenido principal -->
<div class="main-content panel-dark">
  <!-- Sección centrada del logo Fersus -->
  <div class="flex-item">
    <a href="https://fersus.com.mx/">
      <img src="assets/img/logo.png" alt="Logo" class="logo">
    </a>
  </div>
  <div class="card">
    <h2>Búsqueda de PDF</h2>
    <form action="buscar_pdf.php" method="get">
      <div class="form-group">
        <label for="fechaBusqueda">Buscar por Fecha:</label>
        <input type="date" id="fechaBusqueda" name="fechaBusqueda" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-secondary mb-2">Buscar PDF</button>
    </form>
  </div>

  <!-- Si deseas mostrar aquí resultados, podrías hacerlo 
       o redirigir a buscar_pdf.php que procese y muestre. -->
</div>

<?php
include 'layout/footer.php';
?>
