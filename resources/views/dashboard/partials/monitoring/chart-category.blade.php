<div class="col-12 col-lg-4 d-flex align-items-stretch chart-card">
    <div class="card flex-fill d-flex flex-column" style="padding-left: 10px; padding-right: 10px;">
        <div class="card-header" style="flex-shrink: 0;">
            <h5 class="card-title mb-0">Aduan Mengikut Kategori</h5>
            <p class="card-subtitle text-muted mb-0">Top categories</p>
        </div>
        <div class="card-body flex-grow-1" style="padding: 0.25rem; min-height: 0;">
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.registerMonitoringChartPlugins) {
            window.registerMonitoringChartPlugins();
        }

        let categoryChart = null;

        function loadCategoryChart() {
            fetch('{{ route('dashboard.monitoring.category-chart') }}')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const ctx = document.getElementById('categoryChart');
                        const isCompactViewport = window.innerWidth <= 1366;

                        // Destroy existing chart if it exists
                        if (categoryChart) {
                            categoryChart.destroy();
                        }

                        // Create new chart
                        categoryChart = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: result.data.labels,
                                datasets: [{
                                    data: result.data.counts,
                                    backgroundColor: [
                                        'rgba(54, 162, 235, 0.8)',
                                        'rgba(255, 206, 86, 0.8)',
                                        'rgba(75, 192, 192, 0.8)',
                                        'rgba(153, 102, 255, 0.8)',
                                        'rgba(255, 99, 132, 0.8)',
                                        'rgba(255, 159, 64, 0.8)',
                                        'rgba(46, 184, 92, 0.8)',
                                        'rgba(91, 91, 91, 0.8)'
                                    ],
                                    borderWidth: 2,
                                    borderColor: '#fff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: window.isMonitoringMobile && window.isMonitoringMobile() ?
                                    '40%' : '44%',
                                layout: window.getMonitoringDoughnutLayoutOptions ?
                                    window.getMonitoringDoughnutLayoutOptions() : {},
                                plugins: {
                                    // Legend (bottom)
                                    legend: window.getMonitoringDoughnutLegendOptions ?
                                        window.getMonitoringDoughnutLegendOptions(0.85) : {
                                            position: 'bottom',
                                            labels: {
                                                color: '#4b5563',
                                                boxWidth: 16,
                                                boxHeight: 10,
                                                padding: 12,
                                                font: {
                                                    size: window.getChartFont ? window.getChartFont(
                                                        0.85) : 12
                                                }
                                            }
                                        },
                                    // Data label (top on donut)
                                    datalabels: window.getMonitoringDoughnutDataLabelOptions ?
                                        window.getMonitoringDoughnutDataLabelOptions(
                                            isCompactViewport ? 1.05 : 1.5) : {
                                            color: '#fff',
                                            font: {
                                                weight: 'bold',
                                                size: window.getChartFont ? window.getChartFont(
                                                    isCompactViewport ? 1.05 : 1.25) : (
                                                    isCompactViewport ? 11 : 14)
                                            },
                                            formatter: function(value) {
                                                if (value > 0) {
                                                    return value;
                                                }
                                                return '';
                                            }
                                        }
                                }
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading category chart:', error);
                });
        }

        // Initial load
        loadCategoryChart();
        window.monitoringChartReloaders = window.monitoringChartReloaders || [];
        window.monitoringChartReloaders.push(loadCategoryChart);

        // Refresh every 60 seconds
        setInterval(loadCategoryChart, 60000);
    });
</script>
