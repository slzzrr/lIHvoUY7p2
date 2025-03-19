<?php
session_start();
include '../config.php';
include '../auth.php';
requireRoles(['admin', 'viewer', 'colaborador']); // Ajusta roles según tu necesidad

// Incluir layout
include '../layout/header.php';
include '../layout/sidebar.php';
include '../layout/topbar.php';
?>

<div class="container" style="padding:20px; text-align:center;">
  <h1>Registro de Asistencia</h1>
  <p id="statusMessage" style="font-size:1.2em; margin-bottom:20px;">Iniciando escaneo...</p>
  <!-- Contenedor para el escáner -->
  <div id="reader" style="width:300px; margin: auto;"></div>
</div>

<!-- Incluir la librería html5-qrcode -->
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
  let scanner;

  // Función para iniciar el escáner automáticamente
  function startScanner() {
    scanner = new Html5Qrcode("reader");
    scanner.start(
      { facingMode: "environment" },
      { fps: 10, qrbox: 250 },
      onScanSuccess,
      onScanFailure
    ).then(() => {
      document.getElementById("statusMessage").innerText = "Escaneando...";
    }).catch(err => {
      console.error("Error al iniciar el escáner:", err);
      document.getElementById("statusMessage").innerText = "Error al iniciar el escáner.";
    });
  }

  // Función que se ejecuta cuando se detecta un QR
  function onScanSuccess(decodedText, decodedResult) {
    // Detener el escáner para evitar múltiples lecturas
    scanner.stop().then(() => {
      console.log("Escáner detenido.");
      // Se asume que el QR tiene un formato: "EMPLOYEE:123|NAME:Juan Pérez|POSITION:Gerente|EMAIL:juan@example.com"
      const parts = decodedText.split("|");
      let employeeId = null;
      parts.forEach(function(part) {
        if (part.startsWith("EMPLOYEE:")) {
          employeeId = part.replace("EMPLOYEE:", "").trim();
        }
      });
      if (employeeId) {
        document.getElementById("statusMessage").innerText = "Registrando asistencia...";
        // Llamar al backend para registrar la asistencia
        fetch("registrar_asistencia.php?id=" + employeeId)
          .then(response => response.text())
          .then(data => {
            document.getElementById("statusMessage").innerText = data;
            // Reiniciar el escaneo después de 3 segundos
            setTimeout(() => {
              startScanner();
            }, 3000);
          })
          .catch(err => {
            console.error("Error en la solicitud:", err);
            document.getElementById("statusMessage").innerText = "Error al registrar asistencia.";
            setTimeout(() => {
              startScanner();
            }, 3000);
          });
      } else {
        document.getElementById("statusMessage").innerText = "Código QR inválido.";
        setTimeout(() => {
          startScanner();
        }, 3000);
      }
    }).catch(err => {
      console.error("Error al detener el escáner:", err);
      document.getElementById("statusMessage").innerText = "Error al detener el escáner.";
    });
  }

  // Función opcional para manejar errores de escaneo
  function onScanFailure(errorMessage) {
    // Opcional: se pueden manejar los errores si se desea
    console.warn("Fallo en el escaneo: " + errorMessage);
  }

  // Inicia el escáner automáticamente al cargar la página
  startScanner();
</script>

<?php
include '../layout/footer.php';
?>
