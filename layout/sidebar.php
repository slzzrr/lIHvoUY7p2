<?php
// layout/sidebar.php

// Si no se incluyó antes, nos aseguramos de incluir config.php para tener BASE_URL
if (!defined('BASE_URL')) {
    include_once __DIR__ . '/../config.php';
}
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <!-- Logo con ruta absoluta a la carpeta assets/img/ -->
    <img src="<?php echo BASE_URL; ?>assets/img/soursop.png" alt="Logo" class="logo">
  </div>

  <nav class="sidebar-nav">
    <ul>
      <!-- Módulos originales -->
      <li>
        <a href="<?php echo BASE_URL; ?>index.php">
          <i class="bi bi-house-door"></i> Inicio
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>formulario.php">
          <i class="bi bi-file-earmark-text"></i> Formulario
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>busqueda.php">
          <i class="bi bi-search"></i> Búsqueda PDF
        </a>
      </li>

      <!-- Nuevo Submenú Empleados -->
      <li class="has-submenu">
        <a href="#">
          <i class="bi bi-people-fill"></i> Empleados
        </a>
        <ul class="submenu">
          <li>
            <a href="<?php echo BASE_URL; ?>empleados/index.php">
              Registro
            </a>
          </li>
          <li>
            <a href="<?php echo BASE_URL; ?>empleados/listado_colaboradores.php">
              Listado
            </a>
          </li>
          <li>
            <a href="<?php echo BASE_URL; ?>empleados/vacaciones.php">
              Vacaciones
            </a>
          </li>
          <li>
            <a href="<?php echo BASE_URL; ?>empleados/correo.php">
              Correo
            </a>
          </li>
          <li>
            <a href="<?php echo BASE_URL; ?>empleados/tarjetas.php">
              Tarjeta
            </a>
          </li>
          <li>
            <a href="<?php echo BASE_URL; ?>empleados/asistencia.php">
              Escaneo
            </a>
          </li>
          <li>
            <a href="<?php echo BASE_URL; ?>empleados/asistencia_dashboard.php">
              Asistencia
            </a>
          </li>
          <li>
            <a href="<?php echo BASE_URL; ?>empleados/salarios.php">
              Salarios
            </a>
          </li>
          <li>
            <a href="<?php echo BASE_URL; ?>empleados/listado_salarios.php">
              Listado de salarios
            </a>
          </li>
        </ul>
      </li>

      <!-- Contact y Logout -->
      <li>
        <a href="<?php echo BASE_URL; ?>contact.php">
          <i class="bi bi-envelope"></i> Contact Admin
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>logout.php">
          <i class="bi bi-box-arrow-right"></i> Cerrar sesión
        </a>
      </li>
    </ul>
  </nav>
</aside>
