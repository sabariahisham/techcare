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

          // Create the donut chart
          const statusChartVar = new Chart(statusChart, {
            type: 'doughnut',
            data: {
              labels: labels,
              datasets: [
                {
                  data: counts,
                  backgroundColor: colors,
                  borderWidth: 0,
                  pointStyle: 'rectRounded'
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              animation: {
                duration: 800
              },
              cutout: '75%',
              plugins: {
                legend: {
                  display: true,
                  position: 'bottom',
                  labels: {
                    usePointStyle: true,
                    padding: 20,
                    boxWidth: 8,
                    boxHeight: 8,
                    color: headingColor,
                    font: {
                      size: 13
                    }
                  }
                },
                tooltip: {
                  callbacks: {
                    label: function (context) {
                      const label = context.label || '';
                      const value = context.parsed;
                      return ' ' + label + ': ' + value + ' tiket';
                    }
                  },
                  backgroundColor: cardColor,
                  titleColor: headingColor,
                  bodyColor: bodyColor,
                  borderWidth: 1,
                  borderColor: borderColor
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
})();
