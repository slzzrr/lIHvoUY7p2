// tarjeta.js

document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".tarjeta-form form");
    if (form) {
      form.addEventListener("submit", (e) => {
        // Podrías mostrar un “Cargando PDF...” o algo así
      });
    }
  });
  document.addEventListener("DOMContentLoaded", function() {
    const printBtn = document.getElementById("printButton");
    if (printBtn) {
      printBtn.addEventListener("click", function() {
        window.print();
      });
    }
  });