<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}

include 'auth.php';
include 'config.php';
requireRoles(['admin', 'colaborador', 'viewer']);

// 1) Stats para la topbar
$queryProductos = "SELECT COUNT(*) AS total FROM productos";
$resProd = $conexion->query($queryProductos);
$totalProductos = $resProd->fetch_assoc()['total'] ?? 0;

$queryReportes = "SELECT COUNT(DISTINCT folio) AS total FROM reportes";
$resReps = $conexion->query($queryReportes);
$totalReportes = $resReps->fetch_assoc()['total'] ?? 0;

// 2) Viajes generados por usuario (Gráfica pastel)
$usuarios = [];
$cantidades = [];
$queryViajes = "
  SELECT u.nombre AS usuario, COUNT(r.id) AS total_viajes
  FROM reportes r
  JOIN usuarios u ON r.usuario_genero = u.id
  GROUP BY u.id
";
$resultViajes = $conexion->query($queryViajes);
while ($row = $resultViajes->fetch_assoc()) {
  $usuarios[] = $row['usuario'];
  $cantidades[] = (int)$row['total_viajes'];
}

// 3) Usuarios registrados por mes (Gráfica barras horizontales)
$meses = [];
$usuariosMes = [];
$queryUsuariosMes = "
  SELECT DATE_FORMAT(fecha_registro, '%Y-%m') AS mes, COUNT(*) AS total 
  FROM usuarios
  GROUP BY mes
  ORDER BY mes ASC
";
$resultUsuariosMes = $conexion->query($queryUsuariosMes);
while ($row = $resultUsuariosMes->fetch_assoc()) {
  $meses[] = $row['mes'];
  $usuariosMes[] = (int)$row['total'];
}

// Layout
include 'layout/header.php';
include 'layout/sidebar.php';
include 'layout/topbar.php';
?>

<div class="overlay"></div>
<div class="main-content panel-dark">
  <h2 style="text-align:center; margin-bottom: 30px;">Bienvenido al Dashboard</h2>
  <!-- Sección centrada del logo Fersus -->
  <div class="flex-item">
    <a href="https://fersus.com.mx/">
      <img src="assets/img/logo.png" alt="Logo" class="logo">
    </a>
  </div>

  <!-- Stats centradas (Productos, Viajes) -->
  <div class="stats-center">
    <div class="stat-card">
      <span class="stat-icon">📦</span>
      <span class="stat-value"><?php echo $totalProductos; ?></span>
      <span class="stat-label">Productos</span>
    </div>
    <div class="stat-card">
      <span class="stat-icon">🚚</span>
      <span class="stat-value"><?php echo $totalReportes; ?></span>
      <span class="stat-label">Viajes</span>
    </div>
  </div>

  <!-- Contenedor flex para colocar las dos gráficas en una sola fila -->
  <div class="charts-row">
    <!-- Gráfica 1: pastel -->
    <div class="chart-card">
      <h3>Viajes por Usuario</h3>
      <canvas id="viajesUsuariosChart"></canvas>
    </div>
    <!-- Gráfica 2: barras horizontales -->
    <div class="chart-card">
      <h3>Usuarios Registrados por Mes</h3>
      <canvas id="usuariosChart"></canvas>
    </div>
  </div>
</div>

<?php
include 'layout/footer.php';
?>

<!-- Cargamos Chart.js y nuestro archivo de gráficas -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/charts.js"></script>

<script>
  // Datos de la primera gráfica (pastel)
  const usuarios = <?php echo json_encode($usuarios); ?>;
  const cantidades = <?php echo json_encode($cantidades); ?>;
  
  // Datos de la segunda gráfica (barras horizontales)
  const meses = <?php echo json_encode($meses); ?>;
  const usuariosMes = <?php echo json_encode($usuariosMes); ?>;

  // Inicializar las gráficas
  initViajesUsuariosPie(usuarios, cantidades);
  initUsuariosChartHorizontal(meses, usuariosMes);
</script>
