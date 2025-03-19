<?php
session_start();
include '../auth.php';
include '../config.php';
requireRoles(['admin']);

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

// Consulta: Resumen de asistencias de hoy
$queryAttendance = "
    SELECT a.hora_registro, a.estado, e.nombre, e.apellido 
    FROM asistencias a
    JOIN empleados e ON a.empleado_id = e.id
    WHERE a.fecha = CURDATE()
    ORDER BY a.hora_registro ASC
";
$resultAttendance = $conexion->query($queryAttendance);

// Consulta: Ranking de llegadas (la primera entrada de cada empleado hoy)
$queryRanking = "
    SELECT e.id, e.nombre, e.apellido, MIN(a.hora_registro) AS primera_entrada
    FROM asistencias a
    JOIN empleados e ON a.empleado_id = e.id
    WHERE a.fecha = CURDATE()
    GROUP BY e.id, e.nombre, e.apellido
    ORDER BY primera_entrada ASC
";
$resultRanking = $conexion->query($queryRanking);

// Consulta: Bono de puntualidad (asistencias del mes actual)
$queryBono = "
    SELECT e.id, e.nombre, e.apellido,
           SUM(CASE WHEN a.estado='A tiempo' THEN 1 ELSE 0 END) AS a_tiempo,
           SUM(CASE WHEN a.estado='Tarde' THEN 1 ELSE 0 END) AS tarde
    FROM asistencias a
    JOIN empleados e ON a.empleado_id = e.id
    WHERE MONTH(a.fecha) = MONTH(CURDATE()) 
      AND YEAR(a.fecha) = YEAR(CURDATE())
    GROUP BY e.id, e.nombre, e.apellido
    ORDER BY a_tiempo DESC
";
$resultBono = $conexion->query($queryBono);

// Incluir layout (header, sidebar, topbar, footer)
include '../layout/header.php';
include '../layout/sidebar.php';
include '../layout/topbar.php';
?>

<!-- Ajustes de estilo: forzamos color de texto a negro (#000) -->
<style>
.container-dashboard {
  max-width: 1000px;
  margin: 20px auto;
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 30px;
  background-color: #fff;
}
table, th, td {
  border: 1px solid #ccc;
}
/* Forzar color de texto en todas las celdas */
th, td {
  padding: 10px;
  text-align: center;
  color: #000; /* <-- Texto en negro */
}

/* Primera fila de cada tabla: título con fondo oscuro */
.section-title {
  background-color: #333; 
  color: #fff;
  font-size: 1.1em;
  font-weight: bold;
  text-align: center;
  padding: 12px;
}

/* Segunda fila de cada tabla (encabezados de columnas) */
thead tr:nth-child(2) th {
  background-color: #555;
  color: #fff;
}

/* Filas sin datos */
.no-data {
  text-align: center;
  padding: 15px;
  color: #666;
}

/* Estado A tiempo / Tarde */
.atime {
  color: green;
  font-weight: bold;
}
.late {
  color: red;
  font-weight: bold;
}
</style>

<div class="container-dashboard">
  <!-- TABLA: Resumen de Asistencias de Hoy -->
  <table>
    <thead>
      <!-- Fila de título principal -->
      <tr>
        <th class="section-title" colspan="3">Resumen de Asistencias de Hoy</th>
      </tr>
      <!-- Fila de encabezados de columna -->
      <tr>
        <th>Empleado</th>
        <th>Hora de Registro</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($resultAttendance->num_rows > 0): ?>
      <?php while ($row = $resultAttendance->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['nombre'] . ' ' . $row['apellido']); ?></td>
          <td><?php echo htmlspecialchars($row['hora_registro']); ?></td>
          <td class="<?php echo ($row['estado'] == 'A tiempo') ? 'atime' : 'late'; ?>">
            <?php echo htmlspecialchars($row['estado']); ?>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td class="no-data" colspan="3">No se han registrado asistencias hoy.</td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>

  <!-- TABLA: Ranking de Llegadas (Hoy) -->
  <table>
    <thead>
      <tr>
        <th class="section-title" colspan="3">Ranking de Llegadas (Hoy)</th>
      </tr>
      <tr>
        <th>Posición</th>
        <th>Empleado</th>
        <th>Primera Entrada</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($resultRanking->num_rows > 0): ?>
      <?php 
        $position = 1;
        while ($row = $resultRanking->fetch_assoc()):
      ?>
        <tr>
          <td><?php echo $position++; ?></td>
          <td><?php echo htmlspecialchars($row['nombre'] . ' ' . $row['apellido']); ?></td>
          <td><?php echo htmlspecialchars($row['primera_entrada']); ?></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td class="no-data" colspan="3">No hay registros de llegadas para hoy.</td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>

  <!-- TABLA: Bono de Puntualidad (Mes Actual) -->
  <table>
    <thead>
      <tr>
        <th class="section-title" colspan="4">Bono de Puntualidad (Mes Actual)</th>
      </tr>
      <tr>
        <th>Empleado</th>
        <th>A Tiempo</th>
        <th>Tarde</th>
        <th>% Puntualidad</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($resultBono->num_rows > 0): ?>
      <?php while ($row = $resultBono->fetch_assoc()):
            $total = $row['a_tiempo'] + $row['tarde'];
            $porcentaje = ($total > 0) ? round(($row['a_tiempo'] / $total) * 100, 2) : 0;
      ?>
        <tr>
          <td><?php echo htmlspecialchars($row['nombre'] . ' ' . $row['apellido']); ?></td>
          <td><?php echo $row['a_tiempo']; ?></td>
          <td><?php echo $row['tarde']; ?></td>
          <td><?php echo $porcentaje . "%"; ?></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td class="no-data" colspan="4">No hay datos de asistencia para el mes actual.</td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php
include '../layout/footer.php';
?>