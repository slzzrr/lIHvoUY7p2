<?php
session_start();
include '../auth.php';
include '../config.php';
requireRoles(['admin']); // Ajusta si deseas que otros roles también accedan

// Parámetro de búsqueda
$search = $_GET['search'] ?? '';

// Construir la consulta
$sql = "SELECT * FROM empleados WHERE 1";
if ($search !== '') {
    $searchLike = "%$search%";
    $sql .= " AND (nombre LIKE ? OR apellido LIKE ? OR ine LIKE ?)";
}
$sql .= " ORDER BY id DESC";

$stmt = $conexion->prepare($sql);
if ($search !== '') {
    $stmt->bind_param('sss', $searchLike, $searchLike, $searchLike);
}
$stmt->execute();
$result = $stmt->get_result();

// Incluir layout
include '../layout/header.php';  // Asegúrate de que 'header.php' incluya <link> a home.css para el fondo difuminado
include '../layout/sidebar.php';
include '../layout/topbar.php';
?>

<style>
.container-dashboard {
  max-width: 1100px;
  margin: 20px auto;
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.container-dashboard h1 {
  text-align: center;
  margin-bottom: 20px;
}
/* Barra de búsqueda */
.search-form {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 20px;
}
.search-form input[type="text"] {
  width: 300px;
  max-width: 100%;
  padding: 10px;
  font-size: 1em;
  border: 1px solid #ccc;
  border-radius: 4px;
}
.search-form button {
  padding: 10px 20px;
  font-size: 1em;
  margin-left: 10px;
  cursor: pointer;
  background-color: #0066cc;
  color: #fff;
  border: none;
  border-radius: 4px;
}
.search-form button:hover {
  background-color: #004999;
}
.export-buttons {
  text-align: center;
  margin-bottom: 20px;
}
.export-buttons a {
  display: inline-block;
  padding: 10px 20px;
  margin: 5px;
  background-color: #0066cc;
  color: #fff;
  text-decoration: none;
  border-radius: 4px;
}
.export-buttons a:hover {
  background-color: #004999;
}
/* Card para la tabla */
.card-table {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  overflow: hidden;
}
/* Contenedor responsive para la tabla */
.table-responsive {
  width: 100%;
  overflow-x: auto;
}
/* Estilo de la tabla */
.table {
  width: 100%;
  border-collapse: collapse;
  background-color: #fff;
  min-width: 800px; /* Mínimo ancho para escritorio */
}
.table th, .table td {
  border: 1px solid #ccc;
  padding: 10px;
  text-align: center;
  color: #000;
}
.table thead th.section-title {
  background-color: #333;
  color: #fff;
  font-size: 1.1em;
  font-weight: bold;
  text-align: center;
  padding: 12px;
}
.table thead tr:nth-child(2) th {
  background-color: #555;
  color: #fff;
}
.no-data {
  text-align: center;
  padding: 15px;
  color: #666;
}
/* Responsive (pantallas pequeñas) */
@media (max-width: 768px) {
  .table {
    min-width: 600px;
  }
  .search-form input[type="text"] {
    width: 100%;
    margin-bottom: 10px;
  }
  .search-form button {
    margin-left: 0;
    width: 100%;
  }
}
</style>

<div class="overlay"></div>
<div class="main-content panel-dark">
  <!-- Logo de la empresa -->
  <div class="flex-item">
    <a href="https://fersus.com.mx/">
      <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Logo" class="logo">
    </a>
  </div>

  <h1>Listado de Colaboradores</h1>

  <!-- Barra de búsqueda -->
  <form method="GET" action="listado_colaboradores.php" class="search-form">
    <input type="text" name="search" placeholder="Buscar por nombre, apellido o INE" 
           value="<?php echo htmlspecialchars($search); ?>">
    <button type="submit">Buscar</button>
  </form>

  <!-- Botones de exportación -->
  <div class="export-buttons">
    <a href="export_colaboradores_excel.php?search=<?php echo urlencode($search); ?>">Exportar a Excel</a>
    <a href="export_colaboradores_pdf.php?search=<?php echo urlencode($search); ?>">Exportar a PDF</a>
  </div>

  <div class="card-table">
    <div class="table-responsive">
      <table class="table employee-table">
        <thead>
          <tr>
            <th class="section-title" colspan="18">Listado de Colaboradores</th>
          </tr>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Sexo</th>
            <!-- INE (Status) -->
            <th>INE (Status)</th>
            <th>INE Frontal</th>
            <th>INE Trasero</th>
            <th>Tipo Sangre</th>
            <th>Dirección</th>
            <th>Teléfono</th>
            <th>Contacto Emergencia</th>
            <th>Num. Seguro Social</th>
            <th>Puesto</th>
            <th>Grado Estudio</th>
            <th>Fecha Ingreso</th>
            <th>Email</th>
            <th>Fecha Nacimiento</th>
            <th>Foto</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                <td><?php echo htmlspecialchars($row['apellido']); ?></td>
                <td><?php echo htmlspecialchars($row['sexo']); ?></td>
                
                <!-- INE (Status): "Sí hay INE" si al menos uno de ine_frontal o ine_trasero existe -->
                <td>
                  <?php
                  $ineExists = false;
                  if (($row['ine_frontal'] && file_exists($row['ine_frontal'])) ||
                      ($row['ine_trasero'] && file_exists($row['ine_trasero']))) {
                      $ineExists = true;
                  }
                  echo $ineExists ? "Sí hay INE" : "No hay INE";
                  ?>
                </td>

                <!-- INE Frontal -->
                <td>
                  <?php if ($row['ine_frontal'] && file_exists($row['ine_frontal'])): ?>
                    <img src="<?php echo $row['ine_frontal']; ?>" alt="INE Frontal" style="width:50px; height:auto;">
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </td>

                <!-- INE Trasero -->
                <td>
                  <?php if ($row['ine_trasero'] && file_exists($row['ine_trasero'])): ?>
                    <img src="<?php echo $row['ine_trasero']; ?>" alt="INE Trasero" style="width:50px; height:auto;">
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </td>

                <td><?php echo htmlspecialchars($row['tipo_sangre']); ?></td>
                <td><?php echo htmlspecialchars($row['direccion']); ?></td>
                <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                <td><?php echo htmlspecialchars($row['contacto_emergencia']); ?></td>
                <td><?php echo htmlspecialchars($row['num_seguro_social']); ?></td>
                <td><?php echo htmlspecialchars($row['puesto']); ?></td>
                <td><?php echo htmlspecialchars($row['grado_estudio']); ?></td>
                <td><?php echo htmlspecialchars($row['fecha_ingreso']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['fecha_nacimiento']); ?></td>
                <td>
                  <?php if ($row['foto'] && file_exists($row['foto'])): ?>
                    <img src="<?php echo $row['foto']; ?>" alt="Foto" style="width:50px; height:auto;">
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td class="no-data" colspan="18">No se encontraron colaboradores.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
include '../layout/footer.php';
?>
