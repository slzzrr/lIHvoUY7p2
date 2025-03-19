<?php
session_start();
// Si deseas que solo usuarios logueados vean la página de contacto, 
// verifica la sesión:
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// (Opcional) Consultar estadísticas para la topbar
include 'auth.php';
include 'config.php';
requireRoles(['admin', 'colaborador']);
$queryProductos = "SELECT COUNT(*) as total FROM productos";
$resProd = $conexion->query($queryProductos);
$totalProductos = $resProd->fetch_assoc()['total'] ?? 0;

$queryReportes = "SELECT COUNT(DISTINCT folio) as total FROM reportes";
$resReps = $conexion->query($queryReportes);
$totalReportes = $resReps->fetch_assoc()['total'] ?? 0;

// Incluir tu layout
include 'layout/header.php';   // Abre <html><head> + carga home.css
include 'layout/sidebar.php';  // Barra lateral
include 'layout/topbar.php';   // Barra superior
?>

<!-- Overlay para el fondo (lo maneja home.css) -->
<div class="overlay"></div>

<!-- Contenido principal con .panel-dark para el fondo semitransparente -->
<div class="main-content panel-dark">
  <h2>Contacto con el Administrador</h2>
  <p>Puedes mandar mensaje en cualquier momento.</p>
  
  <!-- Ejemplo: botón de WhatsApp -->
  <a href="https://api.whatsapp.com/send/?phone=526676903354&text=Tengo+una+duda+con+el+formulario+de+transporte"
     class="btn btn-secondary">
    Contactar por WhatsApp
  </a>
</div>

<?php
// Cerrar layout
include 'layout/footer.php';
?>
