<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}

include 'auth.php';
include 'config.php';
requireRoles(['admin', 'colaborador']);

// Opcional: Obtener stats para topbar (si lo deseas)
$queryProductos = "SELECT COUNT(*) as total FROM productos";
$resProd = $conexion->query($queryProductos);
$totalProductos = $resProd->fetch_assoc()['total'] ?? 0;

$queryReportes = "SELECT COUNT(DISTINCT folio) as total FROM reportes";
$resReps = $conexion->query($queryReportes);
$totalReportes = $resReps->fetch_assoc()['total'] ?? 0;

// Incluimos tu layout
include 'layout/header.php';
include 'layout/sidebar.php';
include 'layout/topbar.php';
?>

<!-- Overlay sobre la imagen de fondo -->
<div class="overlay"></div>

<!-- Contenido principal con fondo oscuro -->
<div class="main-content panel-dark">
  <!-- Notificación de éxito -->
  <div id="notification" class="notification"></div>

  <h2>Buscar Reportes</h2>
  
  <div class="row">
    <div class="col-md-12">
      <div class="card" style="margin-top: 20px;">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Fecha de Carga</th>
              <th>Fecha de Entrega</th>
              <th>Generado por</th>
              <th>Eliminado por</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if (!empty($_GET['fechaBusqueda'])) {
                $fechaBusqueda = $_GET['fechaBusqueda'];
                
                $query = "SELECT r.id, r.fecha_carga, r.fecha_entrega, r.ruta_pdf, 
                                 u1.nombre as generado_por, 
                                 u2.nombre as eliminado_por 
                          FROM reportes r
                          LEFT JOIN usuarios u1 ON r.usuario_genero = u1.id
                          LEFT JOIN usuarios u2 ON r.usuario_borro = u2.id
                          WHERE r.fecha_carga = ?";
                
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("s", $fechaBusqueda);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$row['id']}</td>";
                    echo "<td>{$row['fecha_carga']}</td>";
                    echo "<td>{$row['fecha_entrega']}</td>";
                    echo "<td>" . ($row['generado_por'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($row['eliminado_por'] ?? 'N/A') . "</td>";
                    
                    $pdf_path = 'saves/reporte_' . $row['id'] . '.pdf';
                    echo "<td>";
                    if (!empty($row['ruta_pdf']) && file_exists($pdf_path)) {
                        echo "<a href='$pdf_path' target='_blank' class='btn btn-primary'>Ver PDF</a> ";
                        echo "<button onclick=\"mostrarModal({$row['id']}, '$pdf_path')\" class='btn btn-danger'>Borrar PDF</button>";
                    } else {
                        echo "<span class='text-danger'>No existe</span>";
                    }
                    echo "</td>";
                    
                    echo "</tr>";
                }
                $stmt->close();
            } else {
                echo "<tr><td colspan='6' class='text-center'>Por favor seleccione una fecha de búsqueda.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Confirmación -->
<div id="confirmModal" class="modal">
  <div class="modal-content">
    <h4>Confirmación de Borrado</h4>
    <p>¿Estás seguro de que deseas borrar este PDF?</p>
    <div class="modal-footer">
      <button id="confirmButton" class="btn btn-danger">Sí, borrar</button>
      <button onclick="cerrarModal()" class="btn btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<script>
  function mostrarModal(id, pdfPath) {
    document.getElementById("confirmModal").style.display = "flex";
    document.getElementById("confirmButton").onclick = function() {
      borrarPDF(id, pdfPath);
    };
  }

  function cerrarModal() {
    document.getElementById("confirmModal").style.display = "none";
  }

  function borrarPDF(id, pdfPath) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "eliminar_pdf.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4 && xhr.status === 200) {
        mostrarNotificacion(xhr.responseText);
      }
    };
    xhr.send("id=" + id + "&pdfPath=" + encodeURIComponent(pdfPath));
    cerrarModal();
  }

  function mostrarNotificacion(mensaje) {
    var notificacion = document.getElementById("notification");
    notificacion.innerText = mensaje;
    notificacion.style.display = "block";
    setTimeout(function() {
      notificacion.style.display = "none";
      location.reload();
    }, 3000);
  }
</script>

<?php
include 'layout/footer.php';
?>
