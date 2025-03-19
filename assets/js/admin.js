// assets/js/admin.js

document.addEventListener("DOMContentLoaded", () => {
    const openSidebarBtn = document.getElementById("openSidebar");
    const closeSidebarBtn = document.getElementById("closeSidebar");
    const sidebar = document.getElementById("sidebar");
  
    // Botón hamburguesa para abrir sidebar
    if (openSidebarBtn) {
      openSidebarBtn.addEventListener("click", () => {
        sidebar.classList.add("show");
      });
    }
    // Botón "X" para cerrar sidebar
    if (closeSidebarBtn) {
      closeSidebarBtn.addEventListener("click", () => {
        sidebar.classList.remove("show");
      });
    }
  });
  