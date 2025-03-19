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
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/loader.css">
<div id="loader-wrapper">
    <div id="loader">
        <!-- SVG de camión con el texto "FERSUS" en la caja -->
        <svg version="1.1" id="truckIcon" xmlns="http://www.w3.org/2000/svg" 
             xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
             viewBox="0 0 512 512" xml:space="preserve">
          <!-- Camión en color blanco -->
          <path fill="#ffffff" d="M480,192h-96V96c0-17.673-14.327-32-32-32H48C21.486,64,0,85.486,0,112v192
            c0,26.514,21.486,48,48,48h32c0,35.346,28.654,64,64,64s64-28.654,64-64h128c0,35.346,28.654,64,64,64
            s64-28.654,64-64h32c26.514,0,48-21.486,48-48V224C512,213.488,498.512,208,480,192z M112,432
            c-17.673,0-32-14.327-32-32s14.327-32,32-32s32,14.327,32,32S129.673,432,112,432z M400,432
            c-17.673,0-32-14.327-32-32s14.327-32,32-32s32,14.327,32,32S417.673,432,400,432z M464,192h-80V128h80V192z"/>
          
          <!-- Texto FERSUS en la caja:
               "FER" (azul), "S" (blanco), "US" (azul).
               Ajusta x, y, font-size para la posición y tamaño exactos. -->
          <text x="205" y="230" text-anchor="middle" font-size="60" font-family="Arial" font-weight="bold">
            <tspan fill="#0066cc">FER</tspan>
            <tspan fill="#7c7c7c">S</tspan>
            <tspan fill="#0066cc">US</tspan>
          </text>
          <text x="217" y="270" text-anchor="middle" font-size="40" font-family="Arial" font-weight="bold">
            <tspan fill="#7c7c7c">transportes</tspan>
          </text>
        </svg>
    </div>
</div>

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
