/**
 * Dashboard Status Chart
 */

import logger from '../logger.js';

'use strict';

// Ensure config is available (colour using Sneat template colors)
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

  /**
   * Build a non-repeating pastel color list for chart slices.
   * Uses Sneat-like soft palette first, then generates distinct pastel HSL colors.
   */
  function generateUniqueChartColors(count, basePalette = []) {
    const colors = [];
    const safeCount = Math.max(0, count || 0);
    const used = new Set();

    for (let index = 0; index < safeCount; index++) {
      let color = basePalette[index];

      if (!color || used.has(color)) {
        const hue = Math.round((index * 137.508) % 360); // Golden angle spread
        const saturation = 62; // Soft pastel saturation
        const lightness = 76;  // Pastel brightness like Sneat style
        color = `hsl(${hue}, ${saturation}%, ${lightness}%)`;
      }

      used.add(color);
      colors.push(color);
    }

    return colors;
  }

  // Status colors mapping using lighter/softer Sneat template colors
  const statusColors = {
    'Aduan Baharu': '#a5abff',    // Lighter Primary - Soft Purple
    'Aduan Diproses': '#ffc947',  // Lighter Warning - Soft Orange
    'Serahan Kepada': '#26d0f0',  // Lighter Info - Soft Cyan
    'Aduan Dirujuk': '#ff6b47',   // Lighter Danger - Soft Red
    'Aduan DiBatal': '#a8b1ba',   // Lighter Secondary - Soft Gray
    'Aduan Selesai': '#98e85d'    // Lighter Success - Soft Green
  };

  // Status Tiket Chart (horizontal bar chart)
  // --------------------------------------------------------------------
  const statusChart = document.getElementById('statusChart');
  if (statusChart) {
    logger.log('Status chart canvas found, initializing...');

    // Fetch data from the backend
    fetch('/dashboard/status-tickets')
      .then(response => {
        logger.log('Response received:', response);
        return response.json();
      })
      .then(data => {
        logger.log('Chart data received:', data);

        if (data.success) {
          const labels = data.data.map(item => item.st_name);
          const counts = data.data.map(item => item.count);
          const colors = data.data.map(item => statusColors[item.st_name] || '#6c757d');

          logger.log('Chart labels:', labels);
          logger.log('Chart counts:', counts);
          logger.log('Chart colors:', colors);

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

          logger.log('Chart created successfully');
        } else {
          console.error('API returned success: false');
        }
      })
      .catch(error => {
        console.error('Error fetching status data:', error);
      });
  } else {
    logger.log('Status chart canvas not found on this page');
  }

  // Kategori Tiket Chart (Doughnut Chart with Legend)
  // --------------------------------------------------------------------
  const categoryChart = document.getElementById('categoryChart');
  if (categoryChart) {
    logger.log('Category chart canvas found, initializing...');

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
        logger.log('Category response received:', response);
        return response.json();
      })
      .then(data => {
        logger.log('Category chart data received:', data);

        if (data.success) {
          const labels = data.data.map(item => item.cat_name);
          const counts = data.data.map(item => item.count);
          const categoryIds = data.data.map(item => item.id); // Store category IDs for drill-down
          const colors = generateUniqueChartColors(data.data.length, categoryColors);

          logger.log('Category labels:', labels);
          logger.log('Category counts:', counts);
          logger.log('Category IDs:', categoryIds);
          logger.log('Category colors:', colors);

          // Determine legend position based on screen size
          const isSmallScreen = window.innerWidth < 992; // Bootstrap lg breakpoint
          const legendPosition = isSmallScreen ? 'bottom' : 'right';
          const legendMaxWidth = isSmallScreen ? null : 180;

          // Variables for drill-down feature
          let currentHoverIndex = -1;
          let categoryDrilldownModal = null;

          // Initialize Bootstrap modal
          const modalElement = document.getElementById('categoryDrilldownModal');
          if (modalElement && typeof bootstrap !== 'undefined') {
            categoryDrilldownModal = new bootstrap.Modal(modalElement);
          }

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
              radius: isSmallScreen ? '68%' : '78%', // Slightly smaller donut for clearer legend values
              layout: {
                padding: {
                  right: isSmallScreen ? 0 : 10
                }
              },
              // Enable click events for drill-down modal
              onClick: (event, activeElements) => {
                if (activeElements.length > 0) {
                  const index = activeElements[0].index;
                  const categoryId = categoryIds[index];
                  const categoryName = labels[index];

                  // Load and show subcategory data in modal
                  loadSubcategoryData(categoryId, categoryName);
                }
              },
              plugins: {
                legend: {
                  display: true,
                  position: legendPosition,
                  align: 'start',
                  maxWidth: legendMaxWidth,
                  labels: {
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 6,
                    boxWidth: 8,
                    boxHeight: 8,
                    color: headingColor,
                    font: {
                      size: 9,
                      weight: '500'
                    },
                    textAlign: 'left',
                    generateLabels: function (chart) {
                      const data = chart.data;
                      if (data.labels.length && data.datasets.length) {
                        return data.labels.map((label, index) => {
                          const dataset = data.datasets[0];
                          const value = dataset.data[index];
                          // Keep labels compact so value remains visible in legend
                          let displayLabel = label;
                          const maxLegendChars = window.innerWidth < 576
                            ? 12
                            : window.innerWidth < 992
                              ? 16
                              : 20;

                          if (label.length > maxLegendChars) {
                            displayLabel = label.substring(0, maxLegendChars) + '...';
                          }
                          return {
                            text: `${displayLabel}: ${value}`,
                            fillStyle: dataset.backgroundColor[index],
                            strokeStyle: dataset.borderColor,
                            lineWidth: dataset.borderWidth,
                            hidden: false,
                            index: index
                          };
                        });
                      }
                      return [];
                    }
                  },
                  // Add click event to legend items as well
                  onClick: (event, legendItem) => {
                    const index = legendItem.index;
                    const categoryId = categoryIds[index];
                    const categoryName = labels[index];

                    // Load and show subcategory data in modal
                    loadSubcategoryData(categoryId, categoryName);
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
                    },
                    //text
                    afterLabel: function () {
                      return '(Klik Lihat Butiran)';
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

          logger.log('Category chart created successfully');

          // Function to load subcategory data for drill-down modal
          function loadSubcategoryData(categoryId, categoryName) {
            logger.log(`Loading subcategories for category ID: ${categoryId}, Name: ${categoryName}`);

            fetch(`/dashboard/category/${categoryId}/subcategories`)
              .then(response => response.json())
              .then(data => {
                if (data.success && data.data.length > 0) {
                  displaySubcategoryModal(data.data, categoryName);
                } else {
                  // No subcategories found, show message
                  displaySubcategoryModal([], categoryName);
                }
              })
              .catch(error => {
                console.error('Error fetching subcategory data:', error);
                alert('Ralat memuatkan data sub kategori. Sila cuba lagi.');
              });
          }

          // Function to display subcategory data in modal
          function displaySubcategoryModal(subcategories, categoryName) {
            const tableBody = document.getElementById('categoryDrilldownTableBody');
            const tableTitle = document.getElementById('categoryDrilldownTitle');
            const totalElement = document.getElementById('categoryDrilldownTotal');

            if (!tableBody || !categoryDrilldownModal) {
              return;
            }

            // Update title with category name
            if (tableTitle) {
              tableTitle.textContent = `Sub Kategori: ${categoryName}`;
            }

            // Clear existing rows
            tableBody.innerHTML = '';

            // Filter subcategories to show only those with tickets (count > 0)
            const subcategoriesWithTickets = subcategories.filter(subcat => subcat.count > 0);

            // Calculate total tickets
            const totalTickets = subcategoriesWithTickets.reduce((sum, subcat) => sum + subcat.count, 0);

            // Update total in footer
            if (totalElement) {
              totalElement.textContent = totalTickets;
            }

            if (subcategoriesWithTickets.length === 0) {
              // Show no data message
              const row = document.createElement('tr');
              row.innerHTML = `
                <td colspan="3" class="text-center text-muted py-4">
                  <i class="ti ti-inbox me-2"></i>
                  Tiada sub kategori dengan tiket dijumpai
                </td>
              `;
              tableBody.appendChild(row);
            } else {
              // Populate table with subcategory data (only those with tickets)
              subcategoriesWithTickets.forEach((subcat, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                  <td class="text-center">${index + 1}</td>
                  <td class="text-start">${subcat.subcat_name}</td>
                  <td class="text-center"><strong>${subcat.count}</strong></td>
                `;
                tableBody.appendChild(row);
              });
            }

            // Show the modal
            categoryDrilldownModal.show();

            logger.log(`Displaying ${subcategoriesWithTickets.length} subcategories (with tickets) for ${categoryName} in modal`);
            logger.log(`Total tickets: ${totalTickets}`);
          }

          // Handle window resize for responsive legend positioning
          let resizeTimeout;
          window.addEventListener('resize', function () {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function () {
              if (categoryChartVar) {
                const isSmallScreen = window.innerWidth < 992;
                categoryChartVar.options.plugins.legend.position = isSmallScreen ? 'bottom' : 'right';
                categoryChartVar.options.plugins.legend.maxWidth = isSmallScreen ? null : 180;
                categoryChartVar.options.layout.padding.right = isSmallScreen ? 0 : 10;
                categoryChartVar.options.radius = isSmallScreen ? '68%' : '78%';
                categoryChartVar.update();
              }
            }, 250);
          });
        } else {
          console.error('Category API returned success: false');
        }
      })
      .catch(error => {
        console.error('Error fetching category data:', error);
      });
  } else {
    logger.log('Category chart canvas not found on this page');
  }

  // Trend Bulanan Tiket (Line Chart with Year Dropdown)
  // --------------------------------------------------------------------
  const trendChart = document.getElementById('trendChart');
  if (trendChart) {
    logger.log('Trend chart canvas found, initializing...');

    let currentYear = new Date().getFullYear();
    let trendChartInstance = null;

    // Function to load trend data for specific year
    function loadTrendData(year) {
      fetch(`/dashboard/monthly-trend?year=${year}`)
        .then(response => {
          logger.log('Monthly trend response received:', response);
          return response.json();
        })
        .then(data => {
          logger.log('Monthly trend chart data received:', data);

          if (data.success) {
            const labels = data.data.map(item => item.month_name);
            const counts = data.data.map(item => item.count);
            const totalTickets = data.total || 0;

            logger.log('Trend labels:', labels);
            logger.log('Trend counts:', counts);
            logger.log('Total tickets for year:', totalTickets);

            // Update subtitle with total
            const subtitleElement = document.getElementById('trendSubtitle');
            if (subtitleElement) {
              subtitleElement.textContent = `Jumlah ${totalTickets} tiket pada tahun ${year}`;
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

            logger.log('Monthly trend chart created successfully');
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
    logger.log('Trend chart canvas not found on this page');
  }

  // Tiket Aduan: Total Tickets Chart (Doughnut with center text)
  // --------------------------------------------------------------------
  const totalChart = document.getElementById('totalChart');
  if (totalChart) {
    logger.log('Total chart canvas found, initializing...');

    // Total tickets colors using Sneat template colors
    const totalColors = [
      '#a5abff',  // Soft Purple (Tiket Sendiri)
      '#26d0f0'   // Soft Cyan (Tiket Bagi Pihak)
    ];

    // Fetch data from the backend
    fetch('/dashboard/total-tickets')
      .then(response => {
        logger.log('Total tickets response received:', response);
        return response.json();
      })
      .then(data => {
        logger.log('Total tickets chart data received:', data);

        if (data.success) {
          const labels = data.data.map(item => item.type_name);
          const counts = data.data.map(item => item.count);
          const colors = data.data.map((item, index) => totalColors[index % totalColors.length]);
          const totalCount = data.total || counts.reduce((sum, count) => sum + count, 0);

          logger.log('Total labels:', labels);
          logger.log('Total counts:', counts);
          logger.log('Total colors:', colors);
          logger.log('Total count:', totalCount);

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

          logger.log('Total tickets chart created successfully');
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
    logger.log('Total chart canvas not found on this page');
  }

  // Jumlah Tiket Mengikut Pegawai Tugasan Chart (Bar Chart with Monthly Dropdown)
  // --------------------------------------------------------------------
  const assignedChart = document.getElementById('assignedChart');
  if (assignedChart) {
    logger.log('Assigned chart canvas found, initializing...');

    let currentMonth = new Date().getMonth() + 1; // 1-based month
    let currentYear = new Date().getFullYear();
    let currentCategoryId = '';
    let currentSubcategoryId = '';
    let assignedChartInstance = null;

    // Function to load assigned data for specific month/year/category/subcategory
    function loadAssignedData(month, year, categoryId = '', subcategoryId = '') {
      let url = `/dashboard/total-assigned?month=${month}&year=${year}`;
      if (categoryId) {
        url += `&category_id=${categoryId}`;
      }
      if (subcategoryId) {
        url += `&subcategory_id=${subcategoryId}`;
      }
      fetch(url)
        .then(response => {
          logger.log('Assigned tickets response received:', response);
          return response.json();
        })
        .then(data => {
          logger.log('Assigned tickets chart data received:', data);
          logger.log('Debug mode enabled:', data.debug_mode);
          if (data.data && data.data.length > 0) {
            logger.log('First officer data:', data.data[0]);
            if (data.data[0].debug) {
              logger.log('First officer debug info:', data.data[0].debug);
            }
          }

          if (data.success) {
            const labels = data.data.map(item => item.officer_name);
            const completedTickets = data.data.map(item => item.completed_tickets || 0);
            const pendingTickets = data.data.map(item => item.pending_tickets || 0);
            const roles = data.data.map(item => item.role_name);
            const totalTickets = data.total || 0;
            const totalCompleted = data.total_completed || 0;
            const totalPending = data.total_pending || 0;

            logger.log('Assigned labels:', labels);
            logger.log('Completed tickets:', completedTickets);
            logger.log('Pending tickets:', pendingTickets);
            logger.log('Assigned roles:', roles);
            logger.log('Total assigned tickets:', totalTickets);

            // Update subtitle with detailed totals
            const subtitleElement = document.getElementById('assignedSubtitle');
            if (subtitleElement) {
              const monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
              ];
              subtitleElement.textContent = `Jumlah ${totalTickets} tiket (${totalCompleted} selesai, ${totalPending} belum selesai) pada ${monthNames[month - 1]} ${year}`;
            }

            // Update month dropdown
            if (data.available_months && data.available_months.length > 0) {
              updateMonthDropdown(data.available_months, month, year);
            }

            // Destroy existing chart if it exists
            if (assignedChartInstance) {
              assignedChartInstance.destroy();
            }

            // Store data for tooltip callbacks
            const chartData = data;
            const chartLabels = labels;
            const chartCompletedTickets = completedTickets;
            const chartPendingTickets = pendingTickets;

            // Create the assigned tickets grouped vertical bar chart (Pegawai Tugasan only)
            assignedChartInstance = new Chart(assignedChart, {
              type: 'bar',
              data: {
                labels: labels,
                datasets: [
                  {
                    label: 'Aduan Selesai',
                    data: completedTickets,
                    backgroundColor: '#98e85d', // Green for completed
                    borderColor: '#71dd37',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false
                  },
                  {
                    label: 'Belum Selesai',
                    data: pendingTickets,
                    backgroundColor: '#ffeecc', // Light orange for pending
                    //backgroundColor: '#fddf94ff', // Light orange for pending
                    borderColor: '#fddf94ff',
                    //borderColor: '#ffc947',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false
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
                    stacked: true, // Enable stacked bars on x-axis
                    grid: {
                      display: false
                    },
                    ticks: {
                      color: headingColor,
                      font: {
                        size: 11,
                        weight: '500'
                      },
                      maxRotation: 45,
                      minRotation: 0,
                      callback: function (value, index) {
                        // Truncate long names for display
                        const label = chartLabels[index];
                        return label && label.length > 12 ? label.substring(0, 12) + '...' : label;
                      }
                    }
                  },
                  y: {
                    stacked: true, // Enable stacked bars on y-axis
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
                  }
                },
                plugins: {
                  legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: {
                      usePointStyle: true,
                      pointStyle: 'rectRounded',
                      padding: 15,
                      boxWidth: 12,
                      boxHeight: 8,
                      color: headingColor,
                      font: {
                        size: 12,
                        weight: '500'
                      }
                    }
                  },
                  tooltip: {
                    mode: 'index',
                    intersect: false,
                    usePointStyle: true,
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
                        const officerData = chartData.data[index];
                        let title = chartLabels[index] || 'Unknown Officer';

                        // Add user ID for development
                        // if (chartData.debug_mode && officerData && officerData.debug) {
                        //   title += ` (User ID: ${officerData.debug.user_id})`;
                        // }

                        return title;
                      },
                      beforeBody: function (context) {
                        const index = context[0].dataIndex;
                        const completed = chartCompletedTickets[index] || 0;
                        const pending = chartPendingTickets[index] || 0;
                        const total = completed + pending;
                        return `Jumlah Aduan: ${total} tiket`;
                      },
                      label: function (context) {
                        const index = context.dataIndex;
                        const datasetLabel = context.dataset.label;
                        const value = context.parsed.y;
                        const officerData = chartData.data[index];

                        let label = '';
                        if (datasetLabel === 'Aduan Selesai') {
                          label = ` Aduan Selesai: ${value} tiket`;

                          // Add debug info for completed tickets
                          // if (chartData.debug_mode && officerData && officerData.debug &&
                          //   officerData.debug.completed_ticket_ids &&
                          //   officerData.debug.completed_ticket_ids.length > 0)  {
                          //   label += ` (Ticket IDs: ${officerData.debug.completed_ticket_ids.join(', ')})`;
                          // }
                        } else if (datasetLabel === 'Belum Selesai') {
                          label = ` Belum Selesai: ${value} tiket`;

                          // Add debug info for pending tickets
                          // if (chartData.debug_mode && officerData && officerData.debug &&
                          //   officerData.debug.pending_ticket_ids &&
                          //   officerData.debug.pending_ticket_ids.length > 0) {
                          //   label += ` (Ticket IDs: ${officerData.debug.pending_ticket_ids.join(', ')})`;
                          // }
                        } else {
                          label = `${datasetLabel}: ${value} tiket`;
                        }

                        return label;
                      },
                      afterBody: function (context) {
                        if (!chartData.debug_mode) return [];

                        const index = context[0].dataIndex;
                        const officerData = chartData.data[index];

                        /* Display Debug Info */
                        // if (officerData && officerData.debug) {
                        //   const debugLines = [];
                        //   debugLines.push(''); // Empty line separator
                        //   debugLines.push('--- DEBUG INFO ---');
                        //   debugLines.push(`Officer ID: ${officerData.debug.user_id}`);

                        //   if (officerData.debug.all_ticket_ids && officerData.debug.all_ticket_ids.length > 0) {
                        //     debugLines.push(`All Ticket IDs: ${officerData.debug.all_ticket_ids.join(', ')}`);
                        //   }

                        //   if (officerData.debug.chronology_ids && officerData.debug.chronology_ids.length > 0) {
                        //     debugLines.push(`Chronology IDs: ${officerData.debug.chronology_ids.join(', ')}`);
                        //   }

                        //   // Show detailed ticket information
                        //   if (officerData.debug.completed_tickets_detail &&
                        //     officerData.debug.completed_tickets_detail.length > 0) {
                        //     debugLines.push('');
                        //     debugLines.push('Completed Tickets:');
                        //     officerData.debug.completed_tickets_detail.forEach(ticket => {
                        //       debugLines.push(`  • ${ticket.ticket_nolog} (ID: ${ticket.ticket_id}, Status: ${ticket.status})`);
                        //     });
                        //   }

                        //   if (officerData.debug.pending_tickets_detail &&
                        //     officerData.debug.pending_tickets_detail.length > 0) {
                        //     debugLines.push('');
                        //     debugLines.push('Pending Tickets:');
                        //     officerData.debug.pending_tickets_detail.forEach(ticket => {
                        //       debugLines.push(`  • ${ticket.ticket_nolog} (ID: ${ticket.ticket_id}, Status: ${ticket.status})`);
                        //     });
                        //   }

                        //   return debugLines;
                        // }

                        return [];
                      }
                    }
                  }

                }
              }
            });

            logger.log('Assigned tickets chart created successfully');
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
            loadAssignedData(monthData.month, monthData.year, currentCategoryId, currentSubcategoryId);
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

    // Category dropdown event handlers
    function setupCategoryDropdown() {
      const categoryDropdown = document.getElementById('categoryDropdown');
      const selectedCategoryBtn = document.getElementById('selectedCategory');

      if (categoryDropdown && selectedCategoryBtn) {
        categoryDropdown.addEventListener('click', function (e) {
          if (e.target.matches('.dropdown-item')) {
            e.preventDefault();
            const categoryId = e.target.getAttribute('data-category-id');
            const categoryName = e.target.textContent;

            currentCategoryId = categoryId;
            currentSubcategoryId = ''; // Reset subcategory when category changes
            selectedCategoryBtn.textContent = categoryName;

            // Update subcategory dropdown based on selected category
            updateSubcategoryDropdown(categoryId);

            // Reload chart data
            loadAssignedData(currentMonth, currentYear, currentCategoryId, currentSubcategoryId);
          }
        });
      }
    }

    // Subcategory dropdown event handlers
    function setupSubcategoryDropdown() {
      const subcategoryDropdown = document.getElementById('subcategoryDropdown');
      const selectedSubcategoryBtn = document.getElementById('selectedSubcategory');

      if (subcategoryDropdown && selectedSubcategoryBtn) {
        subcategoryDropdown.addEventListener('click', function (e) {
          if (e.target.matches('.dropdown-item')) {
            e.preventDefault();
            const subcategoryId = e.target.getAttribute('data-subcategory-id');
            const subcategoryName = e.target.textContent;

            currentSubcategoryId = subcategoryId;
            selectedSubcategoryBtn.textContent = subcategoryName;

            // Reload chart data
            loadAssignedData(currentMonth, currentYear, currentCategoryId, currentSubcategoryId);
          }
        });
      }
    }

    // Function to update subcategory dropdown based on selected category
    function updateSubcategoryDropdown(categoryId) {
      const subcategoryDropdown = document.getElementById('subcategoryDropdown');
      const selectedSubcategoryBtn = document.getElementById('selectedSubcategory');

      if (subcategoryDropdown && selectedSubcategoryBtn) {
        // Reset to "Semua Subkategori"
        selectedSubcategoryBtn.textContent = 'Subkategori';

        if (categoryId) {
          // Fetch subcategories for the selected category
          fetch(`/api/subcategories/${categoryId}`)
            .then(response => response.json())
            .then(data => {
              subcategoryDropdown.innerHTML = '<li><a class="dropdown-item" href="#" data-subcategory-id="">-- Sila Pilih --</a></li>';

              if (data.success && data.data.length > 0) {
                data.data.forEach(subcategory => {
                  const li = document.createElement('li');
                  const a = document.createElement('a');
                  a.className = 'dropdown-item';
                  a.href = '#';
                  a.setAttribute('data-subcategory-id', subcategory.id);
                  a.textContent = subcategory.subcat_name;
                  li.appendChild(a);
                  subcategoryDropdown.appendChild(li);
                });
              }
            })
            .catch(error => {
              console.error('Error fetching subcategories:', error);
              subcategoryDropdown.innerHTML = '<li><a class="dropdown-item" href="#" data-subcategory-id="">-- Sila Pilih --</a></li>';
            });
        } else {
          // If no category selected, show all subcategories option only
          subcategoryDropdown.innerHTML = '<li><a class="dropdown-item" href="#" data-subcategory-id="">-- Sila Pilih --</a></li>';
        }
      }
    }

    // Initialize dropdown event handlers
    setupCategoryDropdown();
    setupSubcategoryDropdown();

    // Initial load
    loadAssignedData(currentMonth, currentYear, currentCategoryId, currentSubcategoryId);

  } else {
    logger.log('Assigned chart canvas not found on this page');
  }

  // Dashboard Cards Functionality
  // Log Hari Ini
  // Selesai Minggu Ini
  // Belum Selesai
  // Lewat SLA
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

        // Create weekly sparkline bar chart
        const chartCanvas = document.getElementById('weeklyCompletedChart');
        if (chartCanvas && data.success) {
          const ctx = chartCanvas.getContext('2d');

          // Map day names to abbreviations
          const dayAbbreviations = {
            'Isnin': 'I',
            'Selasa': 'S',
            'Rabu': 'R',
            'Khamis': 'K',
            'Jumaat': 'J'
          };

          // Ensure canvas has proper height for sparkline
          chartCanvas.style.height = '50px';
          chartCanvas.height = 50;

          new Chart(ctx, {
            type: 'bar',
            data: {
              labels: data.daily_data.map(item => dayAbbreviations[item.day] || item.day),
              datasets: [{
                data: data.daily_data.map(item => item.count),
                backgroundColor: data.daily_data.map(item => item.count > 0 ? '#98e85d' : '#e4e6e8'),
                borderColor: data.daily_data.map(item => item.count > 0 ? '#71dd37' : '#e4e6e8'),
                borderWidth: 1,
                borderRadius: 2,
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false },
                tooltip: {
                  enabled: true,
                  backgroundColor: 'rgba(0, 0, 0, 0.8)',
                  padding: 8,
                  titleFont: { size: 11, weight: 'bold' },
                  bodyFont: { size: 11 },
                  displayColors: false,
                  callbacks: {
                    title: function (context) {
                      // Show full day name in tooltip
                      const fullDayNames = {
                        'I': 'Isnin',
                        'S': 'Selasa',
                        'R': 'Rabu',
                        'K': 'Khamis',
                        'J': 'Jumaat'
                      };
                      return fullDayNames[context[0].label] || context[0].label;
                    },
                    label: function (context) {
                      return `${context.parsed.y} tiket selesai`;
                    }
                  }
                }
              },
              scales: {
                x: {
                  display: true,
                  grid: {
                    display: false,
                    drawBorder: false
                  },
                  ticks: {
                    font: { size: 10, weight: '500' },
                    color: '#a7acb2',
                    padding: 2
                  }
                },
                y: {
                  display: false,
                  beginAtZero: true,
                  grid: { display: false }
                }
              },
              layout: {
                padding: {
                  top: 5,
                  bottom: 0
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
        const totalElement = document.getElementById('total-tickets-count');
        const percentageElement = document.getElementById('pending-percentage');

        if (countElement && data.success) {
          countElement.innerHTML = `${data.pending_count}`;
        }

        if (totalElement && data.success) {
          totalElement.innerHTML = `${data.total_count}`;
        }

        if (percentageElement && data.success) {
          const completedPercentage = (100 - data.percentage).toFixed(1);
          percentageElement.innerHTML = `${completedPercentage}%<br><small style="font-size: 9px; font-weight: normal;">Telah Disiapkan</small>`;
        }

        // Create semi pie chart
        const chartCanvas = document.getElementById('pendingTicketsChart');
        if (chartCanvas && data.success) {
          const ctx = chartCanvas.getContext('2d');

          // Ensure canvas has proper height
          chartCanvas.style.height = '50px';
          chartCanvas.height = 50;

          const completedPercentage = 100 - data.percentage;
          const completedCount = data.total_count - data.pending_count;

          new Chart(ctx, {
            type: 'doughnut',
            data: {
              datasets: [{
                data: [completedPercentage, data.percentage],
                backgroundColor: [
                  '#28c76f',  // Success green for completed
                  '#f0f2f4'   // Very light gray for pending
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
                        return `Telah Disiapkan: ${completedCount} tiket (${completedPercentage.toFixed(1)}%)`;
                      } else {
                        return `Belum Selesai: ${data.pending_count} tiket (${data.percentage}%)`;
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

  // =============================================================================
  // PEGAWAI TUGASAN SPECIFIC CARD FUNCTIONS
  // These load data from dedicated Pegawai endpoints that always filter by assigned tasks
  // =============================================================================

  // Load Today's Logs Count for Pegawai Tugasan
  function loadTodayLogsCountPegawai() {
    fetch('/dashboard/pegawai/today-logs-count')
      .then(response => response.json())
      .then(data => {
        const element = document.getElementById('pegawai-today-logs-count');
        if (element && data.success) {
          element.innerHTML = data.count;
        }
      })
      .catch(error => {
        console.error('Error fetching pegawai today logs count:', error);
        const element = document.getElementById('pegawai-today-logs-count');
        if (element) {
          element.innerHTML = '0';
        }
      });
  }

  // Load Weekly Completed Tickets for Pegawai Tugasan
  function loadWeeklyCompletedTicketsPegawai() {
    fetch('/dashboard/pegawai/weekly-completed')
      .then(response => response.json())
      .then(data => {
        const countElement = document.getElementById('pegawai-weekly-completed-count');
        if (countElement && data.success) {
          countElement.innerHTML = data.total;
        }

        // Create weekly sparkline bar chart for pegawai
        const chartCanvas = document.getElementById('pegawaiWeeklyCompletedChart');
        if (chartCanvas && data.success) {
          const ctx = chartCanvas.getContext('2d');

          // Map day names to abbreviations
          const dayAbbreviations = {
            'Isnin': 'I',
            'Selasa': 'S',
            'Rabu': 'R',
            'Khamis': 'K',
            'Jumaat': 'J'
          };

          // Ensure canvas has proper height for sparkline
          chartCanvas.style.height = '50px';
          chartCanvas.height = 50;

          new Chart(ctx, {
            type: 'bar',
            data: {
              labels: data.daily_data.map(item => dayAbbreviations[item.day] || item.day),
              datasets: [{
                data: data.daily_data.map(item => item.count),
                backgroundColor: data.daily_data.map(item => item.count > 0 ? '#98e85d' : '#e4e6e8'),
                borderColor: data.daily_data.map(item => item.count > 0 ? '#71dd37' : '#e4e6e8'),
                borderWidth: 1,
                borderRadius: 2,
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false },
                tooltip: {
                  enabled: true,
                  backgroundColor: 'rgba(0, 0, 0, 0.8)',
                  padding: 8,
                  titleFont: { size: 11, weight: 'bold' },
                  bodyFont: { size: 11 },
                  displayColors: false,
                  callbacks: {
                    title: function (context) {
                      const fullDayNames = {
                        'I': 'Isnin',
                        'S': 'Selasa',
                        'R': 'Rabu',
                        'K': 'Khamis',
                        'J': 'Jumaat'
                      };
                      return fullDayNames[context[0].label] || context[0].label;
                    },
                    label: function (context) {
                      return `${context.parsed.y} tiket selesai`;
                    }
                  }
                }
              },
              scales: {
                x: {
                  display: true,
                  grid: {
                    display: false,
                    drawBorder: false
                  },
                  ticks: {
                    font: { size: 10, weight: '500' },
                    color: '#a7acb2',
                    padding: 2
                  }
                },
                y: {
                  display: false,
                  beginAtZero: true,
                  grid: { display: false }
                }
              },
              layout: {
                padding: {
                  top: 5,
                  bottom: 0
                }
              }
            }
          });
        }
      })
      .catch(error => {
        console.error('Error fetching pegawai weekly completed tickets:', error);
        const element = document.getElementById('pegawai-weekly-completed-count');
        if (element) {
          element.innerHTML = '0';
        }
      });
  }

  // Load Pending Tickets Stats for Pegawai Tugasan
  function loadPendingTicketsStatsPegawai() {
    fetch('/dashboard/pegawai/pending-stats')
      .then(response => response.json())
      .then(data => {
        const countElement = document.getElementById('pegawai-pending-tickets-count');
        const totalElement = document.getElementById('pegawai-total-tickets-count');
        const percentageElement = document.getElementById('pegawai-pending-percentage');

        if (countElement && data.success) {
          countElement.innerHTML = `${data.pending_count}`;
        }

        if (totalElement && data.success) {
          totalElement.innerHTML = `${data.total_count}`;
        }

        if (percentageElement && data.success) {
          const completedPercentage = (100 - data.percentage).toFixed(1);
          percentageElement.innerHTML = `${completedPercentage}%<br><small style="font-size: 9px; font-weight: normal;">Telah Disiapkan</small>`;
        }

        // Create semi pie chart for pegawai
        const chartCanvas = document.getElementById('pegawaiPendingTicketsChart');
        if (chartCanvas && data.success) {
          const ctx = chartCanvas.getContext('2d');

          // Ensure canvas has proper height
          chartCanvas.style.height = '50px';
          chartCanvas.height = 50;

          const completedPercentage = 100 - data.percentage;
          const completedCount = data.total_count - data.pending_count;

          new Chart(ctx, {
            type: 'doughnut',
            data: {
              datasets: [{
                data: [completedPercentage, data.percentage],
                backgroundColor: [
                  '#28c76f',  // Success green for completed
                  '#f0f2f4'   // Very light gray for pending
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
                        return `Telah Disiapkan: ${completedCount} tiket (${completedPercentage.toFixed(1)}%)`;
                      } else {
                        return `Belum Selesai: ${data.pending_count} tiket (${data.percentage}%)`;
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
        console.error('Error fetching pegawai pending tickets stats:', error);
        const countElement = document.getElementById('pegawai-pending-tickets-count');
        const percentageElement = document.getElementById('pegawai-pending-percentage');
        if (countElement) countElement.innerHTML = '0';
        if (percentageElement) percentageElement.innerHTML = '0%';
      });
  }

  // Load Overdue SLA Count for Pegawai Tugasan
  function loadOverdueSLACountPegawai() {
    fetch('/dashboard/pegawai/overdue-sla-count')
      .then(response => response.json())
      .then(data => {
        const element = document.getElementById('pegawai-overdue-sla-count');
        if (element && data.success) {
          element.innerHTML = data.count;
        }
      })
      .catch(error => {
        console.error('Error fetching pegawai overdue SLA count:', error);
        const element = document.getElementById('pegawai-overdue-sla-count');
        if (element) {
          element.innerHTML = '0';
        }
      });
  }

  // Initialize Pegawai Tugasan cards
  function initializePegawaiDashboardCards() {
    loadTodayLogsCountPegawai();
    loadWeeklyCompletedTicketsPegawai();
    loadPendingTicketsStatsPegawai();
    loadOverdueSLACountPegawai();
  }

  // =============================================================================
  // PEGAWAI TUGASAN SPECIFIC CHART FUNCTIONS
  // These load chart data from dedicated Pegawai endpoints
  // =============================================================================

  // Status Tiket Chart for Pegawai Tugasan (horizontal bar chart)
  const pegawaiStatusChart = document.getElementById('pegawaiStatusChart');
  if (pegawaiStatusChart) {
    logger.log('Pegawai status chart canvas found, initializing...');

    // Fetch data from pegawai endpoint
    fetch('/dashboard/pegawai/status-tickets')
      .then(response => {
        logger.log('Pegawai status response received:', response);
        return response.json();
      })
      .then(data => {
        logger.log('Pegawai status chart data received:', data);

        if (data.success) {
          const labels = data.data.map(item => item.st_name);
          const counts = data.data.map(item => item.count);
          const colors = data.data.map(item => statusColors[item.st_name] || '#6c757d');

          logger.log('Pegawai status chart labels:', labels);
          logger.log('Pegawai status chart counts:', counts);

          // Create the horizontal bar chart for pegawai
          const pegawaiStatusChartVar = new Chart(pegawaiStatusChart, {
            type: 'bar',
            data: {
              labels: labels,
              datasets: [
                {
                  data: counts,
                  backgroundColor: colors,
                  borderWidth: 1,
                  borderColor: colors.map(color => color.replace('0.8)', '1)')),
                  borderRadius: 4,
                  borderSkipped: false
                }
              ]
            },
            options: {
              indexAxis: 'y',
              responsive: true,
              maintainAspectRatio: false,
              layout: {
                padding: {
                  top: 5,
                  bottom: 5,
                  left: 0,
                  right: 0
                }
              },
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
                      size: 10
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
                      size: 10,
                      weight: '500'
                    },
                    callback: function (value, index) {
                      const label = this.getLabelForValue(value);
                      return label.length > 12 ? label.substring(0, 12) + '...' : label;
                    }
                  }
                }
              },
              plugins: {
                legend: {
                  display: false
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
                    size: 11,
                    weight: '600'
                  },
                  bodyFont: {
                    size: 10
                  }
                }
              }
            }
          });

          logger.log('Pegawai status chart created successfully');
        }
      })
      .catch(error => {
        console.error('Error fetching pegawai status data:', error);
      });
  }

  // ============================================================================
  // CLOSED AFTER UAT (04032026) - KEEPING CODE FOR FUTURE USE
  // Kategori Tiket Chart for Pegawai Tugasan (Doughnut Chart)
  // ============================================================================
  /*
  const pegawaiCategoryChart = document.getElementById('pegawaiCategoryChart');
  if (pegawaiCategoryChart) {
    logger.log('Pegawai category chart canvas found, initializing...');

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

    // Fetch data from pegawai category endpoint
    fetch('/dashboard/pegawai/category-tickets')
      .then(response => {
        logger.log('Pegawai category response received:', response);
        return response.json();
      })
      .then(data => {
        logger.log('Pegawai category chart data received:', data);

        if (data.success) {
          // Display all categories assigned to this Pegawai Tugasan (including count = 0)
          const labels = data.data.map(item => item.cat_name);
          const counts = data.data.map(item => item.count);
          const colors = data.data.map((item, index) => categoryColors[index % categoryColors.length]);

          logger.log('Pegawai category labels:', labels);
          logger.log('Pegawai category counts:', counts);

          // Create the category doughnut chart for pegawai
          const pegawaiCategoryChartVar = new Chart(pegawaiCategoryChart, {
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
              cutout: '60%',
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
                            hidden: false,
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

          logger.log('Pegawai category chart created successfully');
        }
      })
      .catch(error => {
        console.error('Error fetching pegawai category data:', error);
      });
  }
  */

  // Sub Kategori Tiket Chart for Pegawai Tugasan (Doughnut Chart)
  const pegawaiSubcategoryChart = document.getElementById('pegawaiSubcategoryChart');
  if (pegawaiSubcategoryChart) {
    logger.log('Pegawai subcategory chart canvas found, initializing...');

    // Subcategory colors using lighter/softer Sneat template colors
    const subcategoryColors = [
      '#a5abff',  // Soft Purple (Primary)
      '#ffc947',  // Soft Orange (Warning)
      '#26d0f0',  // Soft Cyan (Info)
      '#98e85d',  // Soft Green (Success)
      '#ff6b47',  // Soft Red (Danger)
      '#a8b1ba',  // Soft Gray (Secondary)
      '#d4a5ff',  // Soft Lavender (Additional)
      '#ffb1c1',  // Soft Pink
      '#b8e994',  // Soft Lime
      '#74b9ff',  // Soft Blue
      '#fdcb6e',  // Soft Yellow
      '#fd79a8'   // Soft Rose
    ];

    // Fetch data from pegawai subcategory endpoint
    fetch('/dashboard/pegawai/subcategory-tickets')
      .then(response => {
        logger.log('Pegawai subcategory response received:', response);
        return response.json();
      })
      .then(data => {
        logger.log('Pegawai subcategory chart data received:', data);

        if (data.success) {
          // Display all subcategories assigned to this Pegawai Tugasan (including count = 0)
          const labels = data.data.map(item => item.subcat_name);
          const counts = data.data.map(item => item.count);
          const colors = generateUniqueChartColors(data.data.length, subcategoryColors);

          logger.log('Pegawai subcategory labels:', labels);
          logger.log('Pegawai subcategory counts:', counts);

          const isSmallScreen = window.innerWidth < 992;

          // Create the subcategory doughnut chart for pegawai
          const pegawaiSubcategoryChartVar = new Chart(pegawaiSubcategoryChart, {
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
              layout: {
                padding: {
                  top: 5,
                  bottom: 5,
                  left: 5,
                  right: 5
                }
              },
              animation: {
                duration: 1000,
                animateRotate: true,
                animateScale: false
              },
              cutout: '60%',
              radius: isSmallScreen ? '68%' : '78%', // Keep smaller donut like admin chart
              plugins: {
                legend: {
                  display: true,
                  position: 'right',
                  align: 'center',
                  labels: {
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 8,
                    boxWidth: 8,
                    boxHeight: 8,
                    color: headingColor,
                    font: {
                      size: 10,
                      weight: '500'
                    },
                    generateLabels: function (chart) {
                      const data = chart.data;
                      if (data.labels.length && data.datasets.length) {
                        return data.labels.map((label, index) => {
                          const dataset = data.datasets[0];
                          const value = dataset.data[index];
                          // Truncate long labels
                          const displayLabel = label.length > 15 ? label.substring(0, 15) + '...' : label;
                          return {
                            text: `${displayLabel}: ${value}`,
                            fillStyle: dataset.backgroundColor[index],
                            strokeStyle: dataset.borderColor,
                            lineWidth: dataset.borderWidth,
                            hidden: false,
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
                    size: 11,
                    weight: '600'
                  },
                  bodyFont: {
                    size: 10
                  }
                }
              }
            }
          });

          logger.log('Pegawai subcategory chart created successfully');
        }
      })
      .catch(error => {
        console.error('Error fetching pegawai subcategory data:', error);
      });
  }

  // Initialize all dashboard cards
  function initializeDashboardCards() {
    ensureConsistentHeights();

    // Load Admin/Helpdesk cards (if present on page)
    if (document.getElementById('today-logs-count')) {
      loadTodayLogsCount();
      loadWeeklyCompletedTickets();
      loadPendingTicketsStats();
      loadOverdueSLACount();
    }

    // Load Pegawai Tugasan cards (if present on page)
    if (document.getElementById('pegawai-today-logs-count')) {
      initializePegawaiDashboardCards();
    }
  }

  // Initialize Overdue Tickets DataTable
  // --------------------------------------------------------------------
  function initializeOverdueTable() {
    const tableElement = document.getElementById('table-overdue');
    if (!tableElement) {
      logger.log('Overdue table element not found on this page');
      return;
    }

    logger.log('Initializing overdue tickets DataTable...');

    // Check if DataTables is available
    if (typeof $ === 'undefined' || !$.fn || !$.fn.DataTable) {
      console.error('jQuery or DataTables not available for overdue table initialization');
      return;
    }

    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#table-overdue')) {
      $('#table-overdue').DataTable().destroy();
    }

    const overdueTable = $('#table-overdue').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '/dashboard/list-overdue',
        type: 'GET',
        error: function (xhr, error, thrown) {
          console.error('DataTable error:', error);
          console.error('XHR Response:', xhr);
          console.error('Response Text:', xhr.responseText);
          console.error('Status:', xhr.status);
          if (typeof toastr !== 'undefined') {
            toastr.error('Gagal memuat data tiket overdue: ' + (xhr.responseJSON?.message || error));
          }
        },
        dataSrc: function (json) {
          logger.log('DataTable response:', json);
          return json.data;
        }
      },
      columns: [{
        data: 'DT_RowIndex',
        name: 'DT_RowIndex',
        width: '5%',
        className: 'text-center',
        orderable: false,
        searchable: false
      },
      {
        data: 'ticket_nolog',
        name: 'ticket_nolog',
        width: '15%'
      },
      {
        data: 'kategori',
        name: 'kategori',
        width: '15%'
      },
      {
        data: 'tajuk',
        name: 'tajuk',
        width: '25%'
      },
      {
        data: 'status',
        name: 'status',
        width: '10%',
        className: 'text-center'
      },
      {
        data: 'assigned_to',
        name: 'assigned_to',
        width: '10%'
      },
      {
        data: 'sla_duedate',
        name: 'sla_duedate',
        width: '10%',
        className: 'text-center'
      },
      {
        data: 'days_overdue',
        name: 'days_overdue',
        width: '8%',
        className: 'text-center'
      },
      {
        data: 'action',
        name: 'action',
        width: '7%',
        orderable: false,
        searchable: false,
        className: 'text-center'
      }
      ],
      pageLength: 10,
      lengthMenu: [
        [5, 10, 25, 50],
        [5, 10, 25, 50]
      ],
      order: [
        [7, 'desc']
      ], // Sort by days_overdue descending
      language: {
        processing: "Memproses...",
        lengthMenu: "Papar _MENU_ rekod",
        zeroRecords: "Tiada tiket overdue dijumpai",
        info: "Memapar _START_ hingga _END_ daripada _TOTAL_ rekod",
        infoEmpty: "Memapar 0 hingga 0 daripada 0 rekod",
        infoFiltered: "(ditapis daripada _MAX_ jumlah rekod)",
        search: "Cari:",
        paginate: {
          first: "Pertama",
          last: "Terakhir",
          next: "Seterusnya",
          previous: "Sebelumnya"
        }
      },
      responsive: true,
      autoWidth: false,
      dom: '<"row"<"col-sm-6"l><"col-sm-6"f>><"row"<"col-sm-12"tr>><"row"<"col-sm-5"i><"col-sm-7"p>>',
      drawCallback: function (settings) {
        // Re-initialize tooltips after table redraw
        if (typeof $ !== 'undefined' && $.fn.tooltip) {
          $('[title]').tooltip();
        }

        // Add click handler for view ticket buttons
        $('.view-record').off('click').on('click', function () {
          const ticketUuid = $(this).data('uuid');
          // Navigate to the detailed view page
          window.location.href = '/tickets/' + ticketUuid;
        });
      }
    });

    // Refresh table every 5 minutes for real-time updates
    setInterval(function () {
      if (overdueTable) {
        overdueTable.ajax.reload(null, false);
      }
    }, 300000); // 5 minutes

    logger.log('Overdue tickets DataTable initialized successfully');
  }

  // Initialize Overdue Tickets DataTable for Pegawai Tugasan
  // --------------------------------------------------------------------
  function initializeOverdueTablePegawai() {
    const tableElement = document.getElementById('table-overdue-pegawai');
    if (!tableElement) {
      logger.log('Pegawai overdue table element not found on this page');
      return;
    }

    logger.log('Initializing pegawai overdue tickets DataTable...');

    // Check if DataTables is available
    if (typeof $ === 'undefined' || !$.fn || !$.fn.DataTable) {
      console.error('jQuery or DataTables not available for pegawai overdue table initialization');
      return;
    }

    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#table-overdue-pegawai')) {
      $('#table-overdue-pegawai').DataTable().destroy();
    }

    const overdueTablePegawai = $('#table-overdue-pegawai').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '/dashboard/pegawai/list-overdue',
        type: 'GET',
        error: function (xhr, error, thrown) {
          console.error('Pegawai DataTable error:', error);
          console.error('XHR Response:', xhr);
          console.error('Response Text:', xhr.responseText);
          console.error('Status:', xhr.status);
          if (typeof toastr !== 'undefined') {
            toastr.error('Gagal memuat data tiket overdue: ' + (xhr.responseJSON?.message || error));
          }
        },
        dataSrc: function (json) {
          logger.log('Pegawai DataTable response:', json);
          return json.data;
        }
      },
      columns: [{
        data: 'DT_RowIndex',
        name: 'DT_RowIndex',
        width: '5%',
        className: 'text-center',
        orderable: false,
        searchable: false
      },
      {
        data: 'ticket_nolog',
        name: 'ticket_nolog',
        width: '15%'
      },
      {
        data: 'kategori',
        name: 'kategori',
        width: '15%'
      },
      {
        data: 'tajuk',
        name: 'tajuk',
        width: '25%'
      },
      {
        data: 'status',
        name: 'status',
        width: '10%',
        className: 'text-center'
      },
      {
        data: 'assigned_to',
        name: 'assigned_to',
        width: '10%'
      },
      {
        data: 'sla_duedate',
        name: 'sla_duedate',
        width: '10%',
        className: 'text-center'
      },
      {
        data: 'days_overdue',
        name: 'days_overdue',
        width: '8%',
        className: 'text-center'
      },
      {
        data: 'action',
        name: 'action',
        width: '7%',
        orderable: false,
        searchable: false,
        className: 'text-center'
      }
      ],
      pageLength: 10,
      lengthMenu: [
        [5, 10, 25, 50],
        [5, 10, 25, 50]
      ],
      order: [
        [7, 'desc']
      ], // Sort by days_overdue descending
      language: {
        processing: "Memproses...",
        lengthMenu: "Papar _MENU_ rekod",
        zeroRecords: "Tiada tiket overdue dijumpai",
        info: "Memapar _START_ hingga _END_ daripada _TOTAL_ rekod",
        infoEmpty: "Memapar 0 hingga 0 daripada 0 rekod",
        infoFiltered: "(ditapis daripada _MAX_ jumlah rekod)",
        search: "Cari:",
        paginate: {
          first: "Pertama",
          last: "Terakhir",
          next: "Seterusnya",
          previous: "Sebelumnya"
        }
      },
      responsive: true,
      autoWidth: false,
      dom: '<"row"<"col-sm-6"l><"col-sm-6"f>><"row"<"col-sm-12"tr>><"row"<"col-sm-5"i><"col-sm-7"p>>',
      drawCallback: function (settings) {
        // Re-initialize tooltips after table redraw
        if (typeof $ !== 'undefined' && $.fn.tooltip) {
          $('[title]').tooltip();
        }

        // Add click handler for view ticket buttons
        $('.view-record').off('click').on('click', function () {
          const ticketUuid = $(this).data('uuid');
          // Navigate to the detailed view page
          window.location.href = '/tickets/' + ticketUuid;
        });
      }
    });

    // Refresh table every 5 minutes for real-time updates
    setInterval(function () {
      if (overdueTablePegawai) {
        overdueTablePegawai.ajax.reload(null, false);
      }
    }, 300000); // 5 minutes

    logger.log('Pegawai overdue tickets DataTable initialized successfully');
  }

  // Load cards and initialize overdue table on page ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initializeDashboardCards();
      initializeOverdueTable();
      initializeOverdueTablePegawai();
    });
  } else {
    initializeDashboardCards();
    initializeOverdueTable();
    initializeOverdueTablePegawai();
  }

})();
