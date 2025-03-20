<?php
session_start();
include '../auth.php';
include '../config.php';
requireRoles(['admin']);

// ----------------------------------------------------------------
// 1) Lista de empleados que NO tengan un salario registrado aún
// ----------------------------------------------------------------
// Opción A) Subconsulta con NOT IN
$employeeQuery = "
  SELECT e.id, e.nombre, e.apellido
  FROM empleados e
  WHERE e.id NOT IN (SELECT empleado_id FROM salarios)
  ORDER BY e.nombre ASC
";

// (Alternativamente, podrías usar un LEFT JOIN y filtrar donde s.empleado_id IS NULL)

// Ejecutar la consulta
$employeeResult = $conexion->query($employeeQuery);

// ----------------------------------------------------------------
// 2) Variables de formulario
// ----------------------------------------------------------------
$empleado_id      = $_POST['empleado_id']      ?? '';
$periodicidad     = $_POST['periodicidad']     ?? '';
$anio             = $_POST['anio']             ?? '2024';
$salario_bruto    = $_POST['salario_bruto']    ?? '';
$include_subsidio = isset($_POST['include_subsidio']);
$include_imss     = isset($_POST['include_imss']);
$accion           = $_POST['accion']           ?? '';

$errors  = [];
$success = null;

// ----------------------------------------------------------------
// 3) Variables para el desglose de cálculo
// ----------------------------------------------------------------
$limite_inferior    = 0;
$base               = 0;
$tasa               = 0;
$impuesto_marginal  = 0;
$cuota_fija         = 0;
$impuesto_retener   = 0;
$subsidio           = 0;
$ret_imss           = 0;
$percepcion_efectiva= 0;
$salario_neto       = 0;

// ----------------------------------------------------------------
// 4) Tabla de ISR oficial (ejemplo 2023/2024, mensual)
// ----------------------------------------------------------------
$tablaIsrOficial = [
  [    0.01,     644.58,   1.92,    0.00 ],
  [  644.59,    5470.92,   6.40,   12.38 ],
  [ 5470.93,    9614.66,  10.88,  321.26 ],
  [ 9614.67,   11176.62,  16.00,  772.10 ],
  [11176.63,   13381.47,  17.92, 1022.01 ],
  [13381.48,   26988.50,  21.36, 1417.12 ],
  [26988.51,   42537.58,  23.52, 4323.58 ],
  [42537.59,   81211.25,  30.00, 7980.73 ],
  [81211.26,  108281.67,  32.00,19582.83 ],
  [108281.68, 324845.01,  34.00,28245.36 ],
  [324845.02,999999999.0, 35.00,101876.90],
];

// ----------------------------------------------------------------
// 5) Lógica del formulario: Calcular / Guardar
// ----------------------------------------------------------------
if ($accion === 'calcular' || $accion === 'guardar') {
    // Validaciones
    if (empty($empleado_id)) {
        $errors[] = "Selecciona un colaborador.";
    }
    if (empty($periodicidad)) {
        $errors[] = "Selecciona la periodicidad.";
    }
    if (!is_numeric($salario_bruto) || $salario_bruto <= 0) {
        $errors[] = "Ingresa un salario bruto mensual válido.";
    }

    if (empty($errors)) {
        // (A) Tomar el salario bruto MENSUAL que ingresa el usuario
        $salario_mensual = floatval($salario_bruto);

        // (B) Localizar en la tabla de ISR el rango
        foreach ($tablaIsrOficial as $rango) {
            list($li, $ls, $t, $cf) = $rango;
            if ($salario_mensual >= $li && $salario_mensual <= $ls) {
                $limite_inferior = $li;
                $tasa = $t;
                $cuota_fija = $cf;
                break;
            }
        }

        // (C) Calcular la base
        $base = $salario_mensual - $limite_inferior;
        if ($base < 0) $base = 0;

        // (D) Impuesto marginal
        $impuesto_marginal = $base * ($tasa / 100);

        // (E) Impuesto a retener
        $impuesto_retener = $cuota_fija + $impuesto_marginal;

        // (F) Subsidio (opcional)
        if ($include_subsidio) {
            // Ajusta a la tabla oficial de subsidio si requieres exactitud
            $subsidio = 200.00; // Ejemplo fijo
        }

        // (G) Retenciones IMSS (opcional)
        if ($include_imss) {
            // Para cálculo real, se usan porcentajes específicos IMSS
            $ret_imss = $salario_mensual * 0.03; // Ejemplo 3%
        }

        // (H) Neto mensual
        $percepcion_efectiva = $salario_mensual - $impuesto_retener + $subsidio - $ret_imss;

        // (I) Mostrar el neto en la periodicidad elegida
        $salario_neto = $percepcion_efectiva; // neto mensual
        switch ($periodicidad) {
            case 'quincenal':
                $salario_neto = $percepcion_efectiva / 2;
                break;
            case 'semanal':
                $salario_neto = $percepcion_efectiva / 4.2;
                break;
            case 'diario':
                $salario_neto = $percepcion_efectiva / 30.4;
                break;
            // mensual => se queda tal cual
        }

        // (J) Guardar si se eligió "guardar"
        if ($accion === 'guardar') {
            $stmt = $conexion->prepare("
              INSERT INTO salarios (empleado_id, salario, periodicidad)
              VALUES (?, ?, ?)
            ");
            $stmt->bind_param("ids", $empleado_id, $salario_neto, $periodicidad);
            if ($stmt->execute()) {
                $success = "Salario guardado correctamente.";
            } else {
                $errors[] = "Error al guardar en la base de datos.";
            }
            $stmt->close();
        }
    }
}

// ----------------------------------------------------------------
// 6) Incluir layout (header, sidebar, topbar)
// ----------------------------------------------------------------
include '../layout/header.php';
include '../layout/sidebar.php';
include '../layout/topbar.php';
?>

<!-- Estilos de la página -->
<style>
.container-salarios {
  max-width: 900px;
  margin: 20px auto;
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.container-salarios h1 {
  text-align: center;
  margin-bottom: 20px;
  color: #000;
}
.form-group {
  margin-bottom: 15px;
}
label {
  display: block;
  margin-bottom: 5px;
  color: #000;
}
.form-control {
  width: 100%;
  padding: 10px;
  font-size: 1em;
  border: 1px solid #ccc;
  border-radius: 4px;
}
.btn {
  padding: 10px 20px;
  font-size: 1em;
  background-color: #0066cc;
  color: #fff;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-right: 10px;
}
.btn:hover {
  background-color: #004999;
}
.alert {
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 15px;
}
.alert-success {
  background-color: #d4edda;
  color: #155724;
}
.alert-error {
  background-color: #f8d7da;
  color: #721c24;
}
.desglose-container {
  background: #f9f9f9;
  padding: 15px;
  margin-top: 20px;
  border-radius: 6px;
  color: #000;
}
.desglose-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 5px;
  color: #000;
}
.desglose-item span {
  font-weight: bold;
  color: #000;
}
</style>

<div class="overlay"></div>
<div class="main-content panel-dark">
<div class="flex-item">
    <a href="https://fersus.com.mx/">
      <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Logo" class="logo">
    </a>
  </div>
  <div class="container-salarios">
    <h1>Calculadora de Salario Bruto a Neto</h1>

    <!-- Mostrar errores o éxitos -->
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $err): ?>
          <p><?php echo htmlspecialchars($err); ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success">
        <p><?php echo htmlspecialchars($success); ?></p>
      </div>
    <?php endif; ?>

    <form action="salarios.php" method="post">
      <!-- Empleado -->
      <div class="form-group">
        <label for="empleado_id">Colaborador (sin salario asignado):</label>
        <select name="empleado_id" id="empleado_id" class="form-control" required>
          <option value="">Selecciona un colaborador</option>
          <?php 
          // Mostrar SOLO los empleados que NO tienen salario (gracias a la consulta con NOT IN)
          while($emp = $employeeResult->fetch_assoc()):
          ?>
            <option value="<?php echo $emp['id']; ?>"
              <?php if ($empleado_id == $emp['id']) echo 'selected'; ?>>
              <?php echo htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <!-- Periodicidad -->
      <div class="form-group">
        <label for="periodicidad">Periodicidad de pago:</label>
        <select name="periodicidad" id="periodicidad" class="form-control" required>
          <option value="">Selecciona la periodicidad</option>
          <option value="mensual"   <?php if($periodicidad=='mensual') echo 'selected';?>>Mensual</option>
          <option value="quincenal" <?php if($periodicidad=='quincenal') echo 'selected';?>>Quincenal</option>
          <option value="semanal"   <?php if($periodicidad=='semanal') echo 'selected';?>>Semanal</option>
          <option value="diario"    <?php if($periodicidad=='diario') echo 'selected';?>>Diario</option>
        </select>
      </div>

      <!-- Año -->
      <div class="form-group">
        <label for="anio">Selecciona el año:</label>
        <select name="anio" id="anio" class="form-control">
          <option value="2023" <?php if($anio=='2023') echo 'selected';?>>2023</option>
          <option value="2024" <?php if($anio=='2024') echo 'selected';?>>2024</option>
          <option value="2025" <?php if($anio=='2025') echo 'selected';?>>2025</option>
        </select>
      </div>

      <!-- Salario Bruto -->
      <div class="form-group">
        <label for="salario_bruto">Salario Bruto (Mensual):</label>
        <input type="number" step="0.01" name="salario_bruto" id="salario_bruto"
               class="form-control" placeholder="Ej. 10000.00"
               value="<?php echo htmlspecialchars($salario_bruto); ?>" required>
      </div>

      <!-- Checkboxes -->
      <div class="form-group">
        <input type="checkbox" name="include_subsidio" id="include_subsidio"
          <?php if($include_subsidio) echo 'checked';?>>
        <label for="include_subsidio">Incluir subsidio al empleo</label>
      </div>
      <div class="form-group">
        <input type="checkbox" name="include_imss" id="include_imss"
          <?php if($include_imss) echo 'checked';?>>
        <label for="include_imss">Incluir retenciones IMSS</label>
      </div>

      <!-- Botones -->
      <div class="form-group">
        <button type="submit" name="accion" value="calcular" class="btn">Calcular</button>
        <?php if (!empty($limite_inferior) && empty($errors)): ?>
          <!-- Solo mostramos el botón "Guardar Salario" si ya se calculó sin errores -->
          <button type="submit" name="accion" value="guardar" class="btn">Guardar Salario</button>
        <?php endif; ?>
      </div>
    </form>

    <!-- Desglose de Cálculo -->
    <?php if (($accion === 'calcular' || $accion === 'guardar') && empty($errors)): ?>
      <div class="desglose-container">
        <h3>Desglose de Cálculo</h3>
        <div class="desglose-item">
          <span>Límite inferior:</span>
          <span>$<?php echo number_format($limite_inferior, 2); ?></span>
        </div>
        <div class="desglose-item">
          <span>Base:</span>
          <span>$<?php echo number_format($base, 2); ?></span>
        </div>
        <div class="desglose-item">
          <span>Tasa:</span>
          <span><?php echo number_format($tasa, 2); ?>%</span>
        </div>
        <div class="desglose-item">
          <span>Impuesto marginal:</span>
          <span>$<?php echo number_format($impuesto_marginal, 2); ?></span>
        </div>
        <div class="desglose-item">
          <span>Cuota fija:</span>
          <span>$<?php echo number_format($cuota_fija, 2); ?></span>
        </div>
        <div class="desglose-item">
          <span>Impuesto a retener:</span>
          <span>$<?php echo number_format($impuesto_retener, 2); ?></span>
        </div>
        <div class="desglose-item">
          <span>Subsidio:</span>
          <span>$<?php echo number_format($subsidio, 2); ?></span>
        </div>
        <div class="desglose-item">
          <span>Retenciones IMSS:</span>
          <span>$<?php echo number_format($ret_imss, 2); ?></span>
        </div>
        <hr>
        <div class="desglose-item" style="font-weight:bold;">
          <span>Percepción Efectiva (Neto en <?php echo htmlspecialchars($periodicidad); ?>):</span>
          <span>$<?php echo number_format($percepcion_efectiva, 2); ?></span>
        </div>
      </div>
    <?php endif; ?>

    <p style="margin-top:20px; font-size:0.9em; color:#666;">
      <strong>DUDAS?</strong> Manda mensaje a soursop!
    </p>
  </div>
</div>

<?php include '../layout/footer.php'; ?>
