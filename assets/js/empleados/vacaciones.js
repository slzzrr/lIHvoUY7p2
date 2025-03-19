// vacaciones.js

document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".vacaciones-form form");
    if (form) {
      form.addEventListener("submit", (e) => {
        const fechaInicio = document.getElementById("fecha_inicio").value;
        const fechaFin = document.getElementById("fecha_fin").value;
  
        if (fechaInicio && fechaFin) {
          const start = new Date(fechaInicio);
          const end = new Date(fechaFin);
          if (end < start) {
            e.preventDefault();
            alert("La fecha de fin no puede ser anterior a la fecha de inicio.");
          }
        }
        // Podrías implementar aquí la lógica de “antigüedad” si gustas
      });
    }
  });
  