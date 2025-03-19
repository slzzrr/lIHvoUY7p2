// correo.js

document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".correo-form form");
    const empleadoSelect = document.getElementById("empleado_id");
  
    if (form && empleadoSelect) {
      empleadoSelect.addEventListener("change", () => {
        // Podrías cargar datos del empleado vía AJAX para autocompletar
        // nombre, apellido, etc.
        // Ejemplo:
        // fetch('getEmpleado.php?id=' + empleadoSelect.value)
        //   .then(res => res.json())
        //   .then(data => { ... })
      });
    }
  });
  