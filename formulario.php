<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}

include 'auth.php';
include 'config.php';
requireRoles(['admin', 'colaborador']);

// Obtener stats para topbar (siempre que quieras mostrarlas)
$queryProductos = "SELECT COUNT(*) as total FROM productos";
$resultProductos = $conexion->query($queryProductos);
$totalProductos = $resultProductos->fetch_assoc()['total'] ?? 0;

$queryReportes = "SELECT COUNT(DISTINCT folio) as total FROM reportes";
$resultReportes = $conexion->query($queryReportes);
$totalReportes = $resultReportes->fetch_assoc()['total'] ?? 0;

// Incluir layout
include 'layout/header.php';
include 'layout/sidebar.php';
include 'layout/topbar.php';
?>

<!-- Overlay para el fondo -->
<div class="overlay"></div>

<!-- Contenedor principal -->
<div class="main-content panel-dark">

  <!-- Sección centrada del logo Fersus -->
  <div class="flex-item">
    <a href="https://fersus.com.mx/">
      <img src="assets/img/logo.png" alt="Logo" class="logo">
    </a>
  </div>

  <div class="row">
    <!-- Columna Formulario de Aduanas -->
    <div class="col-md-8">
      <h2>Formulario de Aduanas</h2>

      <form action="save.php" method="post" id="transporte-form">
        <div class="form-group">
          <label for="fechaCarga">Fecha de Carga:</label>
          <input type="date" id="fechaCarga" name="fechaCarga" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="fechaEntrega">Fecha de Entrega:</label>
          <input type="date" id="fechaEntrega" name="fechaEntrega" class="form-control">
        </div>
        <div class="form-group">
          <label for="folio">Folio:</label>
          <input type="text" id="folio" name="folio" class="form-control" readonly>
        </div>

        <h3>Datos del Remitente:</h3>
        <div class="form-group">
          <label for="nombreRemitente">Nombre:</label>
          <input type="text" id="nombreRemitente" name="nombreRemitente" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="direccionRemitente">Dirección:</label>
          <input type="text" id="direccionRemitente" name="direccionRemitente" class="form-control" required>
        </div>

        <h3>Datos del Destinatario:</h3>
        <div class="form-group">
          <label for="nombreDestinatario">Nombre:</label>
          <input type="text" id="nombreDestinatario" name="nombreDestinatario" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="direccionDestinatario">Dirección:</label>
          <input type="text" id="direccionDestinatario" name="direccionDestinatario" class="form-control" required>
        </div>

        <h3>Descripción de Productos:</h3>
        <div class="form-group">
          <label for="cantidadProductos">Número de Productos:</label>
          <select id="cantidadProductos" class="form-control" onchange="generarCamposDeProductos(this.value)" required>
            <option value="">Selecciona el número de productos...</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
            <option value="6">6</option>
            <option value="7">7</option>
            <option value="8">8</option>
            <option value="9">9</option>
            <option value="10">10</option>
          </select>
        </div>
        <div id="productos-container">
          <!-- Campos dinámicos generados por JS -->
        </div>

        <h3>Recepción:</h3>
        <div class="form-group">
          <label for="nombreRecibe">Nombre de quien recibe:</label>
          <input type="text" id="nombreRecibe" name="nombreRecibe" class="form-control">
        </div>
        <div class="form-group">
          <label for="observaciones">Observaciones:</label>
          <textarea id="observaciones" name="observaciones" class="form-control" rows="3"></textarea>
        </div>

        <h3>Datos del Operador:</h3>
        <div class="form-group">
          <label for="nombreOperador">Nombre del Operador:</label>
          <input type="text" id="nombreOperador" name="nombreOperador" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="placasUnidad">Placas de la Unidad:</label>
          <input type="text" id="placasUnidad" name="placasUnidad" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary mb-2">Generar PDF</button>
      </form>
    </div>
  </div> <!-- row -->
</div> <!-- main-content.panel-dark -->

<?php
include 'layout/footer.php';
?>
