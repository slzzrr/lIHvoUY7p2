// assets/js/products.js

function generarCamposDeProductos(cantidad) {
    let html = '';
    for (let i = 1; i <= cantidad; i++) {
      html += `
        <h3>Producto ${i}</h3>
        <div class="form-group">
          <label for="np${i}">NP${i}:</label>
          <input type="text" id="np${i}" name="np${i}" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="descripcion${i}">Descripción${i}:</label>
          <input type="text" id="descripcion${i}" name="descripcion${i}" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="cantidad${i}">Cantidad${i}:</label>
          <input type="number" id="cantidad${i}" name="cantidad${i}" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="so${i}">SO${i}:</label>
          <input type="text" id="so${i}" name="so${i}" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="rma${i}">RMA#${i}:</label>
          <input type="text" id="rma${i}" name="rma${i}" class="form-control" required>
        </div>
      `;
    }
    document.getElementById('productos-container').innerHTML = html;
  }
  