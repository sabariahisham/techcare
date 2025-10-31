/**
 * Dashboard Status Chart
 */

'use strict';

// Ensure config is available
if (typeof window.config === 'undefined') {
  window.config = {
    colors: {
      primary: '#696cff',
      secondary: '#8592a3',
      success: '#71dd37',
      info: '#03c3ec',
      warning: '#ffab00',
      danger: '#ff3e1d',
      cardColor: '#fff',
      bodyBg: '#f5f5f9',
      bodyColor: '#646E78',
      headingColor: '#384551',
      textMuted: '#a7acb2',
      borderColor: '#e4e6e8'
    }
  };
}

(function () {
  // Color Variables using Sneat template config
  const borderColor = window.config?.colors?.borderColor || '#e4e6e8';
  const bodyBg = window.config?.colors?.bodyBg || '#f5f5f9';
  const headingColor = window.config?.colors?.headingColor || '#384551';
  const bodyColor = window.config?.colors?.bodyColor || '#646E78';
  const cardColor = window.config?.colors?.cardColor || '#fff';
  const labelColor = window.config?.colors?.textMuted || '#a7acb2';

  // Status colors mapping using lighter/softer Sneat template colors
  const statusColors = {
    'Aduan Baru': '#a5abff',      // Lighter Primary - Soft Purple
    'Aduan Diproses': '#ffc947',  // Lighter Warning - Soft Orange
    'Serahan Kepada': '#26d0f0',  // Lighter Info - Soft Cyan
    'Aduan Dirujuk': '#ff6b47',   // Lighter Danger - Soft Red
    'Aduan DiBatal': '#a8b1ba',   // Lighter Secondary - Soft Gray
    'Aduan Selesai': '#98e85d'    // Lighter Success - Soft Green
  };  // Status Chart
  // --------------------------------------------------------------------
  const statusChart = document.getElementById('statusChart');
  if (statusChart) {
    console.log('Status chart canvas found, initializing...');

    // Fetch data from the backend
    fetch('/dashboard/status-tickets')
      .then(response => {
        console.log('Response received:', response);
        return response.json();
      })
      .then(data => {
        console.log('Chart data received:', data);

        if (data.success) {
          const labels = data.data.map(item => item.st_name);
          const counts = data.data.map(item => item.count);
          const colors = data.data.map(item => statusColors[item.st_name] || '#6c757d');

          console.log('Chart labels:', labels);
          console.log('Chart counts:', counts);
          console.log('Chart colors:', colors);

          // Create the horizontal bar chart
          const statusChartVar = new Chart(statusChart, {
            type: 'bar',
            data: {
              labels: labels,
              datasets: [
                {
                  data: counts,
                  backgroundColor: colors,
                  borderWidth: 1,
                  borderColor: colors.map(color => color.replace('0.8)', '1)')), // Slightly darker border
                  borderRadius: 4,
                  borderSkipped: false
                }
              ]
            },
            options: {
              indexAxis: 'y', // Makes it horizontal
              responsive: true,
              maintainAspectRatio: false,
              animation: {
                duration: 800
              },
              scales: {
                x: {
                  beginAtZero: true,
                  grid: {
                    color: borderColor,
                    drawBorder: false
                  },
                  ticks: {
                    color: labelColor,
                    font: {
                      size: 11
                    },
                    stepSize: 1,
                    callback: function (value) {
                      return Number.isInteger(value) ? value : '';
                    }
                  }
                },
                y: {
                  grid: {
                    display: false
                  },
                  ticks: {
                    color: headingColor,
                    font: {
                      size: 11,
                      weight: '500'
                    },
                    callback: function (value, index) {
                      const label = this.getLabelForValue(value);
                      return label.length > 15 ? label.substring(0, 15) + '...' : label;
                    }
                  }
                }
              },
              plugins: {
                legend: {
                  display: false // Hide legend for horizontal bar chart
                },
                tooltip: {
                  callbacks: {
                    label: function (context) {
                      const label = context.label || '';
                      const value = context.parsed.x;
                      return ` ${label}: ${value} tiket`;
                    }
                  },
                  backgroundColor: cardColor,
                  titleColor: headingColor,
                  bodyColor: bodyColor,
                  borderWidth: 1,
                  borderColor: borderColor,
                  titleFont: {
                    size: 12,
                    weight: '600'
                  },
                  bodyFont: {
                    size: 11
                  }
                }
              }
            }
          });

          console.log('Chart created successfully');
        } else {
          console.error('API returned success: false');
        }
      })
      .catch(error => {
        console.error('Error fetching status data:', error);
      });
  } else {
    console.log('Status chart canvas not found on this page');
  }

  // Category Chart (Doughnut Chart with Legend)
  // --------------------------------------------------------------------
  const categoryChart = document.getElementById('categoryChart');
  if (categoryChart) {
    console.log('Category chart canvas found, initializing...');

    // Category colors using lighter/softer Sneat template colors
    const categoryColors = [
      '#a5abff',  // Soft Purple (Primary)
      '#ffc947',  // Soft Orange (Warning)
      '#26d0f0',  // Soft Cyan (Info)
      '#98e85d',  // Soft Green (Success)
      '#ff6b47',  // Soft Red (Danger)
      '#a8b1ba',  // Soft Gray (Secondary)
      '#d4a5ff'   // Soft Lavender (Additional)
    ];

    // Fetch data from the backend
    fetch('/dashboard/category-tickets')
      .then(response => {
        console.log('Category response received:', response);
        return response.json();
      })
      .then(data => {
        console.log('Category chart data received:', data);

        if (data.success) {
          const labels = data.data.map(item => item.cat_name);
          const counts = data.data.map(item => item.count);
          const colors = data.data.map((item, index) => categoryColors[index % categoryColors.length]);

          console.log('Category labels:', labels);
          console.log('Category counts:', counts);
          console.log('Category colors:', colors);

          // Create the category doughnut chart (different style from status chart)
          const categoryChartVar = new Chart(categoryChart, {
            type: 'doughnut',
            data: {
              labels: labels,
              datasets: [
                {
                  data: counts,
                  backgroundColor: colors,
                  borderWidth: 2,
                  borderColor: cardColor,
                  hoverBorderWidth: 3,
                  hoverBorderColor: '#fff',
                  pointStyle: 'rectRounded'
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              animation: {
                duration: 1000,
                animateRotate: true,
                animateScale: false
              },
              cutout: '60%', // Different cutout from status chart (75%)
              plugins: {
                legend: {
                  display: true,
                  position: 'right',
                  align: 'center',
                  labels: {
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 15,
                    boxWidth: 10,
                    boxHeight: 10,
                    color: headingColor,
                    font: {
                      size: 11,
                      weight: '500'
                    },
                    generateLabels: function (chart) {
                      const data = chart.data;
                      if (data.labels.length && data.datasets.length) {
                        return data.labels.map((label, index) => {
                          const dataset = data.datasets[0];
                          const value = dataset.data[index];
                          return {
                            text: `${label}: ${value}`,
                            fillStyle: dataset.backgroundColor[index],
                            strokeStyle: dataset.borderColor,
                            lineWidth: dataset.borderWidth,
                            hidden: isNaN(value) || value === 0,
                            index: index
                          };
                        });
                      }
                      return [];
                    }
                  }
                },
                tooltip: {
                  callbacks: {
                    label: function (context) {
                      const label = context.label || '';
                      const value = context.parsed;
                      const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                      const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                      return ` ${label}: ${value} tiket (${percentage}%)`;
                    }
                  },
                  backgroundColor: cardColor,
                  titleColor: headingColor,
                  bodyColor: bodyColor,
                  borderWidth: 1,
                  borderColor: borderColor,
                  titleFont: {
                    size: 12,
                    weight: '600'
                  },
                  bodyFont: {
                    size: 11
                  }
                }
              }
            }
          });

          console.log('Category chart created successfully');
        } else {
          console.error('Category API returned success: false');
        }
      })
      .catch(error => {
        console.error('Error fetching category data:', error);
      });
  } else {
    console.log('Category chart canvas not found on this page');
  }
})();
