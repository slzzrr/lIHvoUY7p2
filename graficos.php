<?php
session_start();

// Verificación de sesión
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}

include 'config.php';

// Ejemplo: obtener algunos datos de BD para la gráfica
// A modo de ejemplo, asumimos que 'productos' tiene un campo 'categoria'
// y que quieres contar cuántos productos por categoría:

$categorias = [];
$cantidades = [];

$query = "SELECT categoria, COUNT(*) AS total FROM productos GROUP BY categoria";
$result = $conexion->query($query);
while ($row = $result->fetch_assoc()) {
  $categorias[] = $row['categoria'];
  $cantidades[] = $row['total'];
}

// También, stats para topbar si quieres
$queryProductos = "SELECT COUNT(*) as total FROM productos";
$resProd = $conexion->query($queryProductos);
$totalProductos = $resProd->fetch_assoc()['total'] ?? 0;

$queryReportes = "SELECT COUNT(DISTINCT folio) as total FROM reportes";
$resReps = $conexion->query($queryReportes);
$totalReportes = $resReps->fetch_assoc()['total'] ?? 0;

// Incluir tu layout
include 'layout/header.php';
include 'layout/sidebar.php';
include 'layout/topbar.php';
?>

<!-- Overlay del fondo -->
<div class="overlay"></div>

<!-- Contenedor principal con panel oscuro -->
<div class="main-content panel-dark">
  <h2>Gráficas de Estadísticas</h2>

  <!-- Sección para tu gráfica con Chart.js -->
  <div class="card">
    <h3>Productos por Categoría</h3>
    <canvas id="productosChart" width="400" height="200"></canvas>
  </div>
  
  <!-- Puedes agregar más tarjetas con diferentes gráficas -->
  <div class="card">
    <h3>Otra Gráfica de Ejemplo</h3>
    <canvas id="otraChart" width="400" height="200"></canvas>
  </div>
</div>

<?php
include 'layout/footer.php';
?>

<!-- 
  1) Cargamos Chart.js desde CDN o local. A continuación te muestro desde CDN. 
  2) Cargamos charts.js con la lógica para inicializar la gráfica.
-->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/charts.js"></script>

<script>
// Ejemplo de pasar los datos PHP a JS usando JSON
// De esta manera, charts.js podrá usarlos sin problema.
const categorias = <?php echo json_encode($categorias); ?>;
const cantidades = <?php echo json_encode($cantidades); ?>;

// Llamamos a la función que creaste en charts.js, pasándole los datos
initProductosChart(categorias, cantidades);

// También podrías inicializar la segunda gráfica:
initOtraChart();
</script>
