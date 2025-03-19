// assets/js/charts.js

function randomColor(opacity = 0.6) {
    const r = Math.floor(Math.random() * 255);
    const g = Math.floor(Math.random() * 255);
    const b = Math.floor(Math.random() * 255);
    return `rgba(${r}, ${g}, ${b}, ${opacity})`;
  }
  
  // 1) Pastel
  function initViajesUsuariosPie(labels, dataValues) {
    const ctx = document.getElementById('viajesUsuariosChart').getContext('2d');
    
    const backgroundColors = labels.map(() => randomColor());
    const borderColors = labels.map(() => randomColor(1));
  
    new Chart(ctx, {
      type: 'pie',
      data: {
        labels: labels,
        datasets: [{
          label: 'Viajes Generados',
          data: dataValues,
          backgroundColor: backgroundColors,
          borderColor: borderColors,
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top'
          }
        }
      }
    });
  }
  
  // 2) Barras horizontales (usuarios registrados por mes)
  function initUsuariosChartHorizontal(labels, dataValues) {
    const ctx = document.getElementById('usuariosChart').getContext('2d');
  
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Usuarios Registrados',
          data: dataValues,
          backgroundColor: 'rgba(255, 206, 86, 0.6)',
          borderColor: 'rgba(255, 206, 86, 1)',
          borderWidth: 1
        }]
      },
      options: {
        indexAxis: 'y', // <--- hace que sea horizontal
        responsive: true,
        scales: {
          x: {
            beginAtZero: true
          }
        }
      }
    });
  }
  