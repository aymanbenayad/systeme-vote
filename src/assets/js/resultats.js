// results.js

document.addEventListener('DOMContentLoaded', () => {
    let chartInstance = null;
    const selectType   = document.getElementById('graphChoice');
    const container    = document.getElementById('graph-container');
    const errorMessage = document.getElementById('resultat-error');
  
    function renderChart(type, labels, data) {
      // Vider et recréer un <canvas>
      container.innerHTML = '';
      const canvas = document.createElement('canvas');
      container.appendChild(canvas);
      const ctx = canvas.getContext('2d');
  
      const colorPalette = [
        'rgba(255, 99, 132, 0.9)',   // rouge
        'rgba(54, 162, 235, 0.9)',   // bleu
        'rgba(255, 206, 86, 0.9)',   // jaune
        'rgba(75, 192, 192, 0.9)',   // turquoise
        'rgba(153, 102, 255, 0.9)',  // violet
        'rgba(255, 159, 64, 0.9)'    // orange
      ];
      
      const borderPalette = [
        'rgba(255, 99, 132, 1)',
        'rgba(54, 162, 235, 1)',
        'rgba(255, 206, 86, 1)',
        'rgba(75, 192, 192, 1)',
        'rgba(153, 102, 255, 1)',
        'rgba(255, 159, 64, 1)'
      ];
      
      const bgColors     = data.map((_, index) => colorPalette[index % colorPalette.length]);
      const borderColors = data.map((_, index) => borderPalette[index % borderPalette.length]);
      
  
      // Détruire l'ancien graphe
      if (chartInstance) {
        chartInstance.destroy();
      }
  
      // Créer le nouveau
      chartInstance = new Chart(ctx, {
        type: type,
        data: {
          labels: labels,
          datasets: [{
            label: 'Nombre de votes',
            data: data,
            backgroundColor: bgColors,
            borderColor: borderColors,
            borderWidth: 1
          }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: '#000', // Texte de la légende en noir (opaque)
                        font: {
                            weight: 'bold'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#000',
                    bodyColor: '#000',
                    borderColor: '#000',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: '#000', // Axe X opaque
                        font: {
                            weight: 'bold'
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.2)' // Lignes plus visibles
                    }
                },
                y: {
                    ticks: {
                        color: '#000', // Axe Y opaque
                        font: {
                            weight: 'bold'
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.2)'
                    }
                }
            }
        }
        
      });
    }
  
    function fetchAndUpdate() {
      fetch('backend/api/resultat.php')
        .then(res => res.json())
        .then(json => {
          if (json.status === 'success') {
            renderChart(selectType.value, json.labels, json.data);
          } else {
            console.error('Erreur API.');
            errorMessage.textContent = json.message;
          }
        })
        .catch(err => console.error('Erreur fetch.'));
    }
  
    // Initialisation
    fetchAndUpdate();
  
    // Re-render à chaque changement de type
    selectType.addEventListener('change', fetchAndUpdate);
  });
  