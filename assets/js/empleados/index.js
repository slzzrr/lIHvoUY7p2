// index.js (Empleados)

// Ejemplo: Al cargar la página, inicializamos la gráfica (si no la inicializas en tu inline script)
document.addEventListener("DOMContentLoaded", () => {
    const canvas = document.getElementById("viajesUsuariosChart");
    if (canvas) {
      // Ejemplo de inicializar Chart.js, si no lo haces en inline script
      // const ctx = canvas.getContext("2d");
      // new Chart(ctx, { ... });
    }
  
    // Validar formulario antes de enviar, por ejemplo:
    const form = document.querySelector(".employee-form form");
    if (form) {
      form.addEventListener("submit", (e) => {
        // Haz validaciones extras si deseas
      });
    }
  });
  