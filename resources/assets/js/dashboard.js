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

  // Total Tickets Chart (Doughnut with center text)
  // --------------------------------------------------------------------
  const totalChart = document.getElementById('totalChart');
  if (totalChart) {
    console.log('Total chart canvas found, initializing...');

    // Total tickets colors using Sneat template colors
    const totalColors = [
      '#a5abff',  // Soft Purple (Tiket Sendiri)
      '#26d0f0'   // Soft Cyan (Tiket Bagi Pihak)
    ];

    // Fetch data from the backend
    fetch('/dashboard/total-tickets')
      .then(response => {
        console.log('Total tickets response received:', response);
        return response.json();
      })
      .then(data => {
        console.log('Total tickets chart data received:', data);

        if (data.success) {
          const labels = data.data.map(item => item.type_name);
          const counts = data.data.map(item => item.count);
          const colors = data.data.map((item, index) => totalColors[index % totalColors.length]);
          const totalCount = data.total || counts.reduce((sum, count) => sum + count, 0);

          console.log('Total labels:', labels);
          console.log('Total counts:', counts);
          console.log('Total colors:', colors);
          console.log('Total count:', totalCount);

          // Update the total count display
          const totalCountElement = document.getElementById('totalCount');
          if (totalCountElement) {
            totalCountElement.textContent = totalCount;
          }

          // Create the total tickets polar area chart
          const totalChartVar = new Chart(totalChart, {
            type: 'polarArea',
            data: {
              labels: labels,
              datasets: [
                {
                  data: counts,
                  backgroundColor: [
                    'rgba(165, 171, 255, 0.7)', // Soft purple with transparency
                    'rgba(38, 208, 240, 0.7)'   // Soft cyan with transparency
                  ],
                  borderColor: [
                    '#a5abff', // Solid purple
                    '#26d0f0'  // Solid cyan
                  ],
                  borderWidth: 2,
                  hoverBorderWidth: 3,
                  hoverBorderColor: '#fff'
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              animation: {
                duration: 1000,
                animateRotate: true,
                animateScale: true
              },
              scales: {
                r: {
                  beginAtZero: true,
                  grid: {
                    color: borderColor,
                    lineWidth: 1
                  },
                  angleLines: {
                    color: borderColor,
                    lineWidth: 1
                  },
                  pointLabels: {
                    color: headingColor,
                    font: {
                      size: 11,
                      weight: '500'
                    }
                  },
                  ticks: {
                    display: false // Hide radial ticks for cleaner look
                  }
                }
              },
              plugins: {
                legend: {
                  display: true,
                  position: 'bottom',
                  align: 'center',
                  labels: {
                    usePointStyle: true,
                    pointStyle: 'rectRounded',
                    padding: 15,
                    boxWidth: 12,
                    boxHeight: 12,
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
                          const total = dataset.data.reduce((sum, val) => sum + val, 0);
                          const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                          return {
                            text: `${label}: ${value} (${percentage}%)`,
                            fillStyle: dataset.backgroundColor[index],
                            strokeStyle: dataset.borderColor[index],
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
                      const value = context.parsed.r;
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

          console.log('Total tickets chart created successfully');
        } else {
          console.error('Total tickets API returned success: false');
        }
      })
      .catch(error => {
        console.error('Error fetching total tickets data:', error);
        // Set fallback total count
        const totalCountElement = document.getElementById('totalCount');
        if (totalCountElement) {
          totalCountElement.textContent = '0';
        }
      });
  } else {
    console.log('Total chart canvas not found on this page');
  }

  // Monthly Trend Chart (Line Chart with Year Dropdown)
  // --------------------------------------------------------------------
  const trendChart = document.getElementById('trendChart');
  if (trendChart) {
    console.log('Trend chart canvas found, initializing...');

    let currentYear = new Date().getFullYear();
    let trendChartInstance = null;

    // Function to load trend data for specific year
    function loadTrendData(year) {
      fetch(`/dashboard/monthly-trend?year=${year}`)
        .then(response => {
          console.log('Monthly trend response received:', response);
          return response.json();
        })
        .then(data => {
          console.log('Monthly trend chart data received:', data);

          if (data.success) {
            const labels = data.data.map(item => item.month_name);
            const counts = data.data.map(item => item.count);
            const totalTickets = data.total || 0;

            console.log('Trend labels:', labels);
            console.log('Trend counts:', counts);
            console.log('Total tickets for year:', totalTickets);

            // Update subtitle with total
            const subtitleElement = document.getElementById('trendSubtitle');
            if (subtitleElement) {
              subtitleElement.textContent = `Total ${totalTickets} tiket pada tahun ${year}`;
            }

            // Update year dropdown
            if (data.available_years && data.available_years.length > 0) {
              updateYearDropdown(data.available_years, year);
            }

            // Destroy existing chart if it exists
            if (trendChartInstance) {
              trendChartInstance.destroy();
            }

            // Create the monthly trend line chart
            trendChartInstance = new Chart(trendChart, {
              type: 'line',
              data: {
                labels: labels,
                datasets: [
                  {
                    label: 'Jumlah Tiket',
                    data: counts,
                    borderColor: '#ffc947', // Soft orange (warning color)
                    backgroundColor: 'rgba(255, 201, 71, 0.1)', // Very light orange fill
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4, // Smooth curves
                    pointBackgroundColor: '#ffc947',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: '#ffab00', // Darker orange on hover (original warning)
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 3
                  }
                ]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                  duration: 1000,
                  easing: 'easeInOutQuart'
                },
                interaction: {
                  intersect: false,
                  mode: 'index'
                },
                scales: {
                  x: {
                    grid: {
                      display: false
                    },
                    ticks: {
                      color: headingColor,
                      font: {
                        size: 12,
                        weight: '500'
                      }
                    }
                  },
                  y: {
                    beginAtZero: true,
                    grid: {
                      display: false,  // Disable Y-axis grid lines
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
                  }
                },
                plugins: {
                  legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                      usePointStyle: true,
                      pointStyle: 'line',
                      padding: 20,
                      boxWidth: 20,
                      boxHeight: 3,
                      color: headingColor,
                      font: {
                        size: 12,
                        weight: '500'
                      }
                    }
                  },
                  tooltip: {
                    backgroundColor: cardColor,
                    titleColor: headingColor,
                    bodyColor: bodyColor,
                    borderWidth: 1,
                    borderColor: borderColor,
                    titleFont: {
                      size: 13,
                      weight: '600'
                    },
                    bodyFont: {
                      size: 12
                    },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                      title: function (context) {
                        return `${context[0].label} ${year}`;
                      },
                      label: function (context) {
                        return ` Jumlah tiket: ${context.parsed.y}`;
                      }
                    }
                  }
                }
              }
            });

            console.log('Monthly trend chart created successfully');
          } else {
            console.error('Monthly trend API returned success: false');
          }
        })
        .catch(error => {
          console.error('Error fetching monthly trend data:', error);
        });
    }

    // Function to update year dropdown
    function updateYearDropdown(availableYears, selectedYear) {
      const yearDropdown = document.getElementById('yearDropdown');
      const selectedYearBtn = document.getElementById('selectedYear');

      if (yearDropdown && selectedYearBtn) {
        // Clear existing items
        yearDropdown.innerHTML = '';

        // Add year options
        availableYears.forEach(year => {
          const li = document.createElement('li');
          const a = document.createElement('a');
          a.className = 'dropdown-item';
          a.href = 'javascript:void(0);';
          a.textContent = year;
          a.addEventListener('click', function () {
            currentYear = year;
            selectedYearBtn.textContent = year;
            loadTrendData(year);
          });
          li.appendChild(a);
          yearDropdown.appendChild(li);
        });

        // Update selected year button
        selectedYearBtn.textContent = selectedYear;
      }
    }

    // Initial load
    loadTrendData(currentYear);

  } else {
    console.log('Trend chart canvas not found on this page');
  }

  // Assigned Tickets Chart (Bar Chart with Monthly Dropdown)
  // --------------------------------------------------------------------
  const assignedChart = document.getElementById('assignedChart');
  if (assignedChart) {
    console.log('Assigned chart canvas found, initializing...');

    let currentMonth = new Date().getMonth() + 1; // 1-based month
    let currentYear = new Date().getFullYear();
    let assignedChartInstance = null;

    // Function to load assigned data for specific month/year
    function loadAssignedData(month, year) {
      fetch(`/dashboard/total-assigned?month=${month}&year=${year}`)
        .then(response => {
          console.log('Assigned tickets response received:', response);
          return response.json();
        })
        .then(data => {
          console.log('Assigned tickets chart data received:', data);

          if (data.success) {
            const labels = data.data.map(item => item.officer_name);
            const counts = data.data.map(item => item.total_tickets);
            const roles = data.data.map(item => item.role_name);
            const totalTickets = data.total || 0;

            console.log('Assigned labels:', labels);
            console.log('Assigned counts:', counts);
            console.log('Assigned roles:', roles);
            console.log('Total assigned tickets:', totalTickets);

            // Generate colors for each bar (using different shades of success color)
            const colors = labels.map((label, index) => {
              const baseColors = [
                '#98e85d', '#71dd37', '#5cb85c', '#4cae4c',
                '#449d44', '#398439', '#2e7d32', '#1b5e20'
              ];
              return baseColors[index % baseColors.length];
            });

            // Update subtitle with total
            const subtitleElement = document.getElementById('assignedSubtitle');
            if (subtitleElement) {
              const monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
              ];
              subtitleElement.textContent = `Total ${totalTickets} tiket pada ${monthNames[month - 1]} ${year}`;
            }

            // Update month dropdown
            if (data.available_months && data.available_months.length > 0) {
              updateMonthDropdown(data.available_months, month, year);
            }

            // Destroy existing chart if it exists
            if (assignedChartInstance) {
              assignedChartInstance.destroy();
            }

            // Create the assigned tickets radar chart (perfect for team comparison)
            assignedChartInstance = new Chart(assignedChart, {
              type: 'radar',
              data: {
                labels: labels.map(name => name.length > 10 ? name.substring(0, 10) + '...' : name),
                datasets: [
                  {
                    label: 'Tiket Ditugaskan',
                    data: counts,
                    backgroundColor: 'rgba(152, 232, 93, 0.2)', // Soft green with transparency
                    borderColor: '#98e85d',
                    borderWidth: 2,
                    pointBackgroundColor: '#71dd37',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: '#5cb85c',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 3,
                    fill: true
                  }
                ]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                  duration: 1000,
                  easing: 'easeInOutQuart'
                },
                scales: {
                  r: {
                    beginAtZero: true,
                    max: Math.max(...counts) + Math.ceil(Math.max(...counts) * 0.3), // Add 30% padding
                    grid: {
                      color: borderColor,
                      lineWidth: 1
                    },
                    angleLines: {
                      color: borderColor,
                      lineWidth: 1
                    },
                    pointLabels: {
                      color: headingColor,
                      font: {
                        size: 11,
                        weight: '500'
                      },
                      callback: function (label) {
                        return label; // Names are already truncated above
                      }
                    },
                    ticks: {
                      color: labelColor,
                      font: {
                        size: 9
                      },
                      stepSize: Math.max(1, Math.ceil(Math.max(...counts) / 5)),
                      callback: function (value) {
                        return Number.isInteger(value) ? value : '';
                      }
                    }
                  }
                },
                plugins: {
                  legend: {
                    display: true,
                    position: 'top',
                    align: 'center',
                    labels: {
                      usePointStyle: true,
                      pointStyle: 'circle',
                      padding: 15,
                      boxWidth: 8,
                      boxHeight: 8,
                      color: headingColor,
                      font: {
                        size: 12,
                        weight: '500'
                      }
                    }
                  },
                  tooltip: {
                    backgroundColor: cardColor,
                    titleColor: headingColor,
                    bodyColor: bodyColor,
                    borderWidth: 1,
                    borderColor: borderColor,
                    titleFont: {
                      size: 13,
                      weight: '600'
                    },
                    bodyFont: {
                      size: 12
                    },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                      title: function (context) {
                        const index = context[0].dataIndex;
                        return labels[index]; // Full name in tooltip
                      },
                      label: function (context) {
                        const index = context.dataIndex;
                        const value = context.parsed.r;
                        return [
                          `Tiket: ${value}`,
                          `Jawatan: ${roles[index]}`
                        ];
                      }
                    }
                  }
                },
                interaction: {
                  intersect: false,
                  mode: 'point'
                }
              }
            });

            console.log('Assigned tickets chart created successfully');
          } else {
            console.error('Assigned tickets API returned success: false');
          }
        })
        .catch(error => {
          console.error('Error fetching assigned tickets data:', error);
        });
    }

    // Function to update month dropdown
    function updateMonthDropdown(availableMonths, selectedMonth, selectedYear) {
      const monthDropdown = document.getElementById('monthDropdown');
      const selectedMonthBtn = document.getElementById('selectedMonth');

      if (monthDropdown && selectedMonthBtn) {
        // Clear existing items
        monthDropdown.innerHTML = '';

        // Add month options
        availableMonths.forEach(monthData => {
          const li = document.createElement('li');
          const a = document.createElement('a');
          a.className = 'dropdown-item';
          a.href = 'javascript:void(0);';
          a.textContent = monthData.display_name;
          a.addEventListener('click', function () {
            currentMonth = monthData.month;
            currentYear = monthData.year;
            selectedMonthBtn.textContent = monthData.display_name;
            loadAssignedData(monthData.month, monthData.year);
          });
          li.appendChild(a);
          monthDropdown.appendChild(li);
        });

        // Update selected month button
        const monthNames = [
          'January', 'February', 'March', 'April', 'May', 'June',
          'July', 'August', 'September', 'October', 'November', 'December'
        ];
        selectedMonthBtn.textContent = `${monthNames[selectedMonth - 1]} ${selectedYear}`;
      }
    }

    // Initial load
    loadAssignedData(currentMonth, currentYear);

  } else {
    console.log('Assigned chart canvas not found on this page');
  }

  // Dashboard Cards Functionality
  // --------------------------------------------------------------------

  // Load Today's Logs Count
  function loadTodayLogsCount() {
    fetch('/dashboard/today-logs-count')
      .then(response => response.json())
      .then(data => {
        const element = document.getElementById('today-logs-count');
        if (element && data.success) {
          element.innerHTML = data.count;
        }
      })
      .catch(error => {
        console.error('Error fetching today logs count:', error);
        const element = document.getElementById('today-logs-count');
        if (element) {
          element.innerHTML = '0';
        }
      });
  }

  // Load Weekly Completed Tickets with Bar Chart
  function loadWeeklyCompletedTickets() {
    fetch('/dashboard/weekly-completed')
      .then(response => response.json())
      .then(data => {
        const countElement = document.getElementById('weekly-completed-count');
        if (countElement && data.success) {
          countElement.innerHTML = data.total;
        }

        // Create weekly bar chart
        const chartCanvas = document.getElementById('weeklyCompletedChart');
        if (chartCanvas && data.success) {
          const ctx = chartCanvas.getContext('2d');

          // Ensure canvas has proper height
          chartCanvas.style.height = '60px';
          chartCanvas.height = 60;

          new Chart(ctx, {
            type: 'bar',
            data: {
              labels: data.daily_data.map(item => item.day),
              datasets: [{
                data: data.daily_data.map(item => item.count),
                backgroundColor: data.daily_data.map(item => item.count > 0 ? '#98e85d' : '#e4e6e8'),
                borderColor: data.daily_data.map(item => item.count > 0 ? '#71dd37' : '#e4e6e8'),
                borderWidth: 1,
                borderRadius: 3,
                borderSkipped: false
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false },
                tooltip: {
                  callbacks: {
                    label: function (context) {
                      return `${context.label}: ${context.parsed.y} tiket`;
                    }
                  }
                }
              },
              scales: {
                x: {
                  display: true,
                  grid: { display: false },
                  ticks: {
                    font: { size: 9 },
                    color: '#a7acb2'
                  }
                },
                y: {
                  display: false,
                  beginAtZero: true
                }
              },
              elements: {
                bar: {
                  borderRadius: 3
                }
              }
            }
          });
        }
      })
      .catch(error => {
        console.error('Error fetching weekly completed tickets:', error);
        const element = document.getElementById('weekly-completed-count');
        if (element) {
          element.innerHTML = '0';
        }
      });
  }

  // Load Pending Tickets Stats with Semi Pie Chart
  function loadPendingTicketsStats() {
    fetch('/dashboard/pending-stats')
      .then(response => response.json())
      .then(data => {
        const countElement = document.getElementById('pending-tickets-count');
        const percentageElement = document.getElementById('pending-percentage');

        if (countElement && data.success) {
          countElement.innerHTML = `${data.pending_count}/${data.total_count}`;
        }

        if (percentageElement && data.success) {
          percentageElement.innerHTML = `${data.percentage}%`;
        }

        // Create semi pie chart
        const chartCanvas = document.getElementById('pendingTicketsChart');
        if (chartCanvas && data.success) {
          const ctx = chartCanvas.getContext('2d');

          // Ensure canvas has proper height
          chartCanvas.style.height = '80px';
          chartCanvas.height = 80;

          new Chart(ctx, {
            type: 'doughnut',
            data: {
              datasets: [{
                data: [data.percentage, 100 - data.percentage],
                backgroundColor: [
                  '#ffab00',  // Warning color for pending
                  '#f0f2f4'   // Very light gray for completed
                ],
                borderWidth: 0,
                cutout: '75%'
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              rotation: -90,  // Start from top
              circumference: 180,  // Semi circle
              plugins: {
                legend: { display: false },
                tooltip: {
                  enabled: true,
                  callbacks: {
                    label: function (context) {
                      if (context.dataIndex === 0) {
                        return `Belum Selesai: ${data.pending_count} tiket (${data.percentage}%)`;
                      } else {
                        return `Selesai: ${data.total_count - data.pending_count} tiket (${(100 - data.percentage).toFixed(1)}%)`;
                      }
                    }
                  }
                }
              }
            }
          });
        }
      })
      .catch(error => {
        console.error('Error fetching pending tickets stats:', error);
        const countElement = document.getElementById('pending-tickets-count');
        const percentageElement = document.getElementById('pending-percentage');
        if (countElement) countElement.innerHTML = '0/0';
        if (percentageElement) percentageElement.innerHTML = '0%';
      });
  }

  // Load Overdue SLA Count
  function loadOverdueSLACount() {
    fetch('/dashboard/overdue-sla-count')
      .then(response => response.json())
      .then(data => {
        const element = document.getElementById('overdue-sla-count');
        if (element && data.success) {
          element.innerHTML = data.count;
        }
      })
      .catch(error => {
        console.error('Error fetching overdue SLA count:', error);
        const element = document.getElementById('overdue-sla-count');
        if (element) {
          element.innerHTML = '0';
        }
      });
  }

  // Function to ensure consistent heights during loading
  function ensureConsistentHeights() {
    // Set minimum heights for all canvas elements to prevent layout shift
    const statusCanvas = document.getElementById('statusChart');
    const categoryCanvas = document.getElementById('categoryChart');
    const weeklyCanvas = document.getElementById('weeklyCompletedChart');
    const pendingCanvas = document.getElementById('pendingTicketsChart');

    if (statusCanvas) {
      statusCanvas.style.minHeight = '220px';
    }

    if (categoryCanvas) {
      categoryCanvas.style.minHeight = '220px';
    }

    if (weeklyCanvas) {
      weeklyCanvas.style.height = '60px';
      weeklyCanvas.height = 60;
    }

    if (pendingCanvas) {
      pendingCanvas.style.height = '80px';
      pendingCanvas.height = 80;
    }
  }

  // Initialize all dashboard cards
  function initializeDashboardCards() {
    ensureConsistentHeights();
    loadTodayLogsCount();
    loadWeeklyCompletedTickets();
    loadPendingTicketsStats();
    loadOverdueSLACount();
  }

  // Load cards on page ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDashboardCards);
  } else {
    initializeDashboardCards();
  }

})();
