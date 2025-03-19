<?php
session_start();
// Subimos un nivel para incluir config.php y tener BASE_URL
include '../auth.php';
include '../config.php';
requireRoles(['admin']);

// Verificar si el usuario inició sesión
if (!isset($_SESSION['user_id'])) {
  header("Location: " . BASE_URL . "login.php");
  exit();
}

// Procesar formulario de registro de empleado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $sexo = $_POST['sexo'] ?? '';
    $ine = $_POST['ine'] ?? '';
    $tipo_sangre = $_POST['tipo_sangre'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $contacto = $_POST['contacto_emergencia'] ?? '';
    $num_seguro = $_POST['num_seguro_social'] ?? '';
    $puesto = $_POST['puesto'] ?? '';
    $grado = $_POST['grado_estudio'] ?? '';
    $fecha_ingreso = $_POST['fecha_ingreso'] ?? '';
    $email = $_POST['email'] ?? '';

    // Manejo de la foto (opcional)
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $targetDir = "../uploads/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fotoPath = $targetDir . basename($_FILES['foto']['name']);
        move_uploaded_file($_FILES['foto']['tmp_name'], $fotoPath);
        $foto = $fotoPath;
    }

    // Insertar en la tabla empleados
    $stmt = $conexion->prepare("
        INSERT INTO empleados
        (nombre, apellido, sexo, ine, tipo_sangre, direccion, telefono,
         contacto_emergencia, num_seguro_social, puesto, grado_estudio,
         fecha_ingreso, email, foto)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssssssssssss",
        $nombre, $apellido, $sexo, $ine, $tipo_sangre, $direccion, $telefono,
        $contacto, $num_seguro, $puesto, $grado, $fecha_ingreso, $email, $foto
    );
    $stmt->execute();
    $stmt->close();

    // Redirigir para limpiar el formulario
    header("Location: index.php");
    exit();
}

// Listar empleados
$query = "SELECT * FROM empleados ORDER BY id DESC";
$result = $conexion->query($query);

// Incluir layout
include '../layout/header.php';   // Carga <head>, home.css (con BASE_URL)
include '../layout/sidebar.php';  // Barra lateral
include '../layout/topbar.php';   // Barra superior
?>

<!-- CSS específico de este módulo (opcional si lo tienes) -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/empleados/index.css">

<div class="overlay"></div>
<div class="main-content panel-dark">

  <!-- Logo de la empresa (opcional) -->
  <div class="flex-item">
    <a href="https://fersus.com.mx/">
      <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Logo" class="logo">
    </a>
  </div>

  <h2>Empleados</h2>

  <!-- Formulario de Registro de Empleado -->
  <div class="card">
    <h3>Registrar Empleado</h3>
    <form action="index.php" method="post" class="employee-form" enctype="multipart/form-data">
      <!-- Nombre y Apellido -->
      <div class="form-group">
        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" id="nombre" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="apellido">Apellido:</label>
        <input type="text" name="apellido" id="apellido" class="form-control" required>
      </div>
      <!-- Sexo -->
      <div class="form-group">
        <label for="sexo">Sexo:</label>
        <select name="sexo" id="sexo" class="form-control" required>
          <option value="">Selecciona</option>
          <option value="M">Masculino</option>
          <option value="F">Femenino</option>
        </select>
      </div>
      <!-- INE -->
      <div class="form-group">
        <label for="ine">INE:</label>
        <input type="text" name="ine" id="ine" class="form-control">
      </div>
      <!-- Tipo de Sangre -->
      <div class="form-group">
        <label for="tipo_sangre">Tipo de Sangre:</label>
        <input type="text" name="tipo_sangre" id="tipo_sangre" class="form-control">
      </div>
      <!-- Dirección -->
      <div class="form-group">
        <label for="direccion">Dirección:</label>
        <input type="text" name="direccion" id="direccion" class="form-control" required>
      </div>
      <!-- Teléfono -->
      <div class="form-group">
        <label for="telefono">Teléfono:</label>
        <input type="text" name="telefono" id="telefono" class="form-control" required>
      </div>
      <!-- Contacto de Emergencia -->
      <div class="form-group">
        <label for="contacto_emergencia">Contacto de Emergencia:</label>
        <input type="text" name="contacto_emergencia" id="contacto_emergencia" class="form-control">
      </div>
      <!-- Número de Seguro Social -->
      <div class="form-group">
        <label for="num_seguro_social">Número de Seguro Social:</label>
        <input type="text" name="num_seguro_social" id="num_seguro_social" class="form-control">
      </div>
      <!-- Puesto -->
      <div class="form-group">
        <label for="puesto">Puesto:</label>
        <input type="text" name="puesto" id="puesto" class="form-control" required>
      </div>
      <!-- Grado de Estudio -->
      <div class="form-group">
        <label for="grado_estudio">Grado de Estudio:</label>
        <input type="text" name="grado_estudio" id="grado_estudio" class="form-control">
      </div>
      <!-- Fecha de Ingreso -->
      <div class="form-group">
        <label for="fecha_ingreso">Fecha de Ingreso:</label>
        <input type="date" name="fecha_ingreso" id="fecha_ingreso" class="form-control" required>
      </div>
      <!-- Email -->
      <div class="form-group">
        <label for="email">Correo Electrónico:</label>
        <input type="email" name="email" id="email" class="form-control">
      </div>
      <!-- Foto (opcional) -->
      <div class="form-group">
        <label for="foto">Foto (opcional):</label>
        <input type="file" name="foto" id="foto" class="form-control">
      </div>

      <button type="submit" class="btn">Registrar Empleado</button>
    </form>
  </div>

  <!-- Lista de Empleados -->
  <div class="card">
    <h3>Listado de Empleados</h3>
    <table class="table employee-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Apellido</th>
          <th>Sexo</th>
          <th>INE</th>
          <th>Tipo Sangre</th>
          <th>Dirección</th>
          <th>Teléfono</th>
          <th>Contacto Emergencia</th>
          <th>Num. Seguro Social</th>
          <th>Puesto</th>
          <th>Grado Estudio</th>
          <th>Fecha Ingreso</th>
          <th>Email</th>
          <th>Foto</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['nombre']; ?></td>
            <td><?php echo $row['apellido']; ?></td>
            <td><?php echo $row['sexo']; ?></td>
            <td><?php echo $row['ine']; ?></td>
            <td><?php echo $row['tipo_sangre']; ?></td>
            <td><?php echo $row['direccion']; ?></td>
            <td><?php echo $row['telefono']; ?></td>
            <td><?php echo $row['contacto_emergencia']; ?></td>
            <td><?php echo $row['num_seguro_social']; ?></td>
            <td><?php echo $row['puesto']; ?></td>
            <td><?php echo $row['grado_estudio']; ?></td>
            <td><?php echo $row['fecha_ingreso']; ?></td>
            <td><?php echo $row['email']; ?></td>
            <td>
              <?php if ($row['foto'] && file_exists($row['foto'])): ?>
                <img src="<?php echo $row['foto']; ?>" alt="Foto" style="width:50px; height:auto;">
              <?php else: ?>
                N/A
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

</div>

<?php
include '../layout/footer.php';
?>
