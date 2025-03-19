<?php
session_start();

// Si tu layout está en la misma carpeta "lok/layout", usa:
include 'layout/header.php';
include 'layout/sidebar.php';
include 'layout/topbar.php';
?>

<div class="container" style="margin: 50px auto; text-align:center;">
  <h1 style="color:#d8000c;">Acceso Denegado</h1>
  <p style="font-size:1.2em;">
    No tienes permiso para ver esta sección.
  </p>
</div>

<?php
include 'layout/footer.php';
?>