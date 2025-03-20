<?php
session_start();
include '../auth.php';
include '../config.php';
requireRoles(['admin']); // Solo el rol 'admin' puede ver este listado

// 1) Parámetro de búsqueda
$search = $_GET['search'] ?? '';

// 2) Construir la consulta
//    Notar que recuperamos s.id AS sid, para poder enlazar a editar_salario.php?id=sid
$sql = "
    SELECT 
      s.id AS sid,
      e.id AS eid,
      CONCAT(e.nombre, ' ', e.apellido) AS empleado,
      s.salario,
      s.periodicidad,
      s.fecha_registro,
      e.email
    FROM empleados e
    LEFT JOIN salarios s ON e.id = s.empleado_id
    WHERE 1
";

// Si hay búsqueda, filtrar por nombre/apellido
if ($search !== '') {
    $sql .= " AND (e.nombre LIKE ? OR e.apellido LIKE ?)";
}

$sql .= " ORDER BY e.id DESC";

// Preparar y ejecutar
$stmt = $conexion->prepare($sql);
if ($search !== '') {
    $like = "%$search%";
    $stmt->bind_param('ss', $like, $like);
}
$stmt->execute();
$result = $stmt->get_result();

// 3) Incluir layout
include '../layout/header.php';
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
  color: #000;
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
  min-width: 800px;
}
.table th, .table td {
  border: 1px solid #ccc;
  padding: 10px;
  text-align: center;
  color: #000; /* Asegura texto negro */
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
<div class="flex-item">
    <a href="https://fersus.com.mx/">
      <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Logo" class="logo">
    </a>
  </div>
  <div class="container-dashboard">
    <!-- Título -->
    <h1>Listado de Salarios</h1>

    <!-- Barra de búsqueda -->
    <form method="GET" action="listado_salarios.php" class="search-form">
      <input type="text" name="search" placeholder="Buscar por nombre o apellido" 
             value="<?php echo htmlspecialchars($search); ?>">
      <button type="submit">Buscar</button>
    </form>

    <!-- Botones de exportación (opcional) -->
    <div class="export-buttons">
      <a href="export_salarios_excel.php?search=<?php echo urlencode($search); ?>">Exportar a Excel</a>
      <a href="export_salarios_pdf.php?search=<?php echo urlencode($search); ?>">Exportar a PDF</a>
    </div>

    <div class="card-table">
      <div class="table-responsive">
        <table class="table salary-table">
          <thead>
            <tr>
              <th class="section-title" colspan="6">Listado de Colaboradores con Salario</th>
            </tr>
            <tr>
              <th>ID Salario</th>
              <th>Colaborador</th>
              <th>Salario (MXN)</th>
              <th>Periodicidad</th>
              <th>Fecha de Registro</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <?php 
                $sid         = $row['sid'];         // ID en la tabla salarios
                $empleado    = $row['empleado'];    // Nombre completo
                $salario     = $row['salario'];     // Salario neto guardado
                $periodicidad= $row['periodicidad'];
                $fecha       = $row['fecha_registro'];
              ?>
              <tr>
                <td><?php echo $sid ? $sid : 'N/A'; ?></td>
                <td><?php echo htmlspecialchars($empleado); ?></td>
                <td>
                  <?php 
                    if (isset($salario)) {
                      echo '$' . number_format($salario, 2);
                    } else {
                      echo 'N/A';
                    }
                  ?>
                </td>
                <td><?php echo $periodicidad ?: 'N/A'; ?></td>
                <td><?php echo $fecha ?: 'N/A'; ?></td>
                <td>
                  <?php if ($sid): ?>
                    <!-- Enlace a editar_salario.php -->
                    <a href="editar_salario.php?id=<?php echo $sid; ?>" class="btn" 
                       style="background:#0066cc; color:#fff; padding:5px 10px; border-radius:4px; text-decoration:none;">
                      Editar
                    </a>
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td class="no-data" colspan="6">No se encontraron registros de salarios.</td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
include '../layout/footer.php';
?>
