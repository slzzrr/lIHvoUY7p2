<?php
session_start();
include '../auth.php';
include '../config.php';
requireRoles(['admin']); // Solo el admin puede registrar nuevos colaboradores

// Verificar si el usuario inició sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

// Procesar formulario de registro de colaborador
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $sexo = $_POST['sexo'] ?? '';
    // El campo INE se dejará opcional (por si se ingresa manualmente)
    $ine = trim($_POST['ine'] ?? '');
    $tipo_sangre = trim($_POST['tipo_sangre'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $contacto = trim($_POST['contacto_emergencia'] ?? '');
    $num_seguro = trim($_POST['num_seguro_social'] ?? '');
    $puesto = trim($_POST['puesto'] ?? '');
    $grado = trim($_POST['grado_estudio'] ?? '');
    $fecha_ingreso = $_POST['fecha_ingreso'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';

    // Validaciones en el servidor
    if (!empty($ine) && !preg_match('/^[A-Za-z0-9]+$/', $ine)) {
        die("INE inválido. Debe contener solo caracteres alfanuméricos.");
    }
    if (!preg_match('/^[0-9]{10}$/', $telefono)) {
        die("Teléfono inválido. Debe contener exactamente 10 dígitos.");
    }
    if (!preg_match('/^[0-9]{10}$/', $contacto)) {
        die("Contacto de Emergencia inválido. Debe contener 10 dígitos.");
    }

    // Manejo de la foto del colaborador (opcional)
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

    // Manejo de las imágenes del INE
    $ine_frontal = null;
    $ine_trasero = null;
    $targetDirINE = "../uploads/ine/";
    if (!file_exists($targetDirINE)) {
        mkdir($targetDirINE, 0777, true);
    }
    if (isset($_FILES['ine_frontal']) && $_FILES['ine_frontal']['error'] === 0) {
        $ineFrontalPath = $targetDirINE . basename($_FILES['ine_frontal']['name']);
        move_uploaded_file($_FILES['ine_frontal']['tmp_name'], $ineFrontalPath);
        $ine_frontal = $ineFrontalPath;
    }
    if (isset($_FILES['ine_trasero']) && $_FILES['ine_trasero']['error'] === 0) {
        $ineTraseroPath = $targetDirINE . basename($_FILES['ine_trasero']['name']);
        move_uploaded_file($_FILES['ine_trasero']['tmp_name'], $ineTraseroPath);
        $ine_trasero = $ineTraseroPath;
    }

    // Insertar en la tabla empleados
    $stmt = $conexion->prepare("
        INSERT INTO empleados
        (nombre, apellido, sexo, ine, tipo_sangre, direccion, telefono,
         contacto_emergencia, num_seguro_social, puesto, grado_estudio,
         fecha_ingreso, email, foto, fecha_nacimiento, ine_frontal, ine_trasero)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssssssssssssss",
        $nombre, $apellido, $sexo, $ine, $tipo_sangre, $direccion, $telefono,
        $contacto, $num_seguro, $puesto, $grado, $fecha_ingreso, $email, $foto, $fecha_nacimiento, $ine_frontal, $ine_trasero
    );
    $stmt->execute();
    $stmt->close();

    header("Location: index.php");
    exit();
}

// Listar colaboradores (ordenados por id descendente)
$query = "SELECT * FROM empleados ORDER BY id DESC";
$result = $conexion->query($query);

// Incluir layout
include '../layout/header.php';
include '../layout/sidebar.php';
include '../layout/topbar.php';
?>

<!-- Puedes colocar estilos profesionales en un archivo CSS (por ejemplo, assets/css/empleados/index.css) -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/empleados/index.css">

<div class="overlay"></div>
  <div class="main-content panel-dark">
  <!-- Logo de la empresa -->
  <div class="flex-item">
    <a href="https://fersus.com.mx/">
      <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Logo" class="logo">
    </a>
  </div>

  <h2>Colaboradores</h2>

  <!-- Formulario de Registro de Colaborador -->
  <div class="card">
    <h3>Registrar Colaborador</h3>
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
      <!-- INE (clave de elector, opcional) -->
      <div class="form-group">
        <label for="ine">Clave del Elector (opcional):</label>
        <input type="text" name="ine" id="ine" class="form-control">
      </div>
      <!-- Imágenes del INE -->
      <div class="form-group">
        <label for="ine_frontal">INE Frontal:</label>
        <input type="file" name="ine_frontal" id="ine_frontal" class="form-control" accept="image/*">
      </div>
      <div class="form-group">
        <label for="ine_trasero">INE Trasero:</label>
        <input type="file" name="ine_trasero" id="ine_trasero" class="form-control" accept="image/*">
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
        <label for="telefono">Teléfono (México, 10 dígitos):</label>
        <input type="tel" name="telefono" id="telefono" class="form-control" required
               pattern="^[0-9]{10}$" maxlength="10" title="Debe contener exactamente 10 dígitos">
      </div>
      <!-- Contacto de Emergencia -->
      <div class="form-group">
        <label for="contacto_emergencia">Contacto de Emergencia (10 dígitos):</label>
        <input type="tel" name="contacto_emergencia" id="contacto_emergencia" class="form-control" required
               pattern="^[0-9]{10}$" maxlength="10" title="Debe contener exactamente 10 dígitos">
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
      <!-- Fecha de Nacimiento -->
      <div class="form-group">
        <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control">
      </div>
      <!-- Foto del colaborador (opcional) -->
      <div class="form-group">
        <label for="foto">Foto (opcional):</label>
        <input type="file" name="foto" id="foto" class="form-control">
      </div>

      <button type="submit" class="btn">Registrar Colaborador</button>
    </form>
  </div>
  
  <!-- Lista de Colaboradores en esta misma página (opcional, si prefieres separarlo en otra) -->
  <div class="card">
    <h3>Listado de Colaboradores</h3>
    <table class="table employee-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Apellido</th>
          <th>Sexo</th>
          <th>INE</th>
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
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
            <td><?php echo htmlspecialchars($row['apellido']); ?></td>
            <td><?php echo htmlspecialchars($row['sexo']); ?></td>
            <td><?php echo htmlspecialchars($row['ine']); ?></td>
            <td>
              <?php if ($row['ine_frontal'] && file_exists($row['ine_frontal'])): ?>
                <img src="<?php echo $row['ine_frontal']; ?>" alt="INE Frontal" style="width:50px; height:auto;">
              <?php else: ?>
                N/A
              <?php endif; ?>
            </td>
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
      </tbody>
    </table>
  </div>

</div>

<?php
include '../layout/footer.php';
?>