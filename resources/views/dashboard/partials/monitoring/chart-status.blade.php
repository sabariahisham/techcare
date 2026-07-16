<div class="col-12 col-lg-4 d-flex align-items-stretch chart-card">
    <div class="card flex-fill d-flex flex-column">
        <div class="card-header" style="flex-shrink: 0;">
            <h5 class="card-title mb-0">Graf Status Aduan</h5>
            <p class="card-subtitle text-muted mb-0">Data real-time status tiket</p>
        </div>
        <div class="card-body flex-grow-1" style="padding: 0.25rem; min-height: 0;">
            <div class="chart-container" style="padding-left: 10px; padding-right: 10px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.registerMonitoringChartPlugins) {
            window.registerMonitoringChartPlugins();
        }

        let statusChart = null;

        function loadStatusChart() {
            fetch('{{ route('dashboard.monitoring.status-chart') }}')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const ctx = document.getElementById('statusChart');

                        // Destroy existing chart if it exists
                        if (statusChart) {
                            statusChart.destroy();
                        }

                        const statusColorMap = {
                            primary: { bg: 'rgba(105, 108, 255, 0.8)', border: 'rgba(105, 108, 255, 1)' },
                            warning: { bg: 'rgba(255, 171, 0, 0.8)', border: 'rgba(255, 171, 0, 1)' },
                            info: { bg: 'rgba(3, 195, 236, 0.8)', border: 'rgba(3, 195, 236, 1)' },
                            danger: { bg: 'rgba(255, 62, 29, 0.8)', border: 'rgba(255, 62, 29, 1)' },
                            secondary: { bg: 'rgba(133, 146, 163, 0.8)', border: 'rgba(133, 146, 163, 1)' },
                            success: { bg: 'rgba(113, 221, 55, 0.8)', border: 'rgba(113, 221, 55, 1)' }
                        };

                        const statusTemplateMap = {
                            'aduan baharu': 'primary',
                            'aduan baru': 'primary',
                            'aduan diproses': 'warning',
                            'serahan kepada': 'info',
                            'aduan dirujuk': 'danger',
                            'aduan dibatal': 'secondary',
                            'aduan dibatalkan': 'secondary',
                            'aduan selesai': 'success'
                        };

                        const normalizedLabels = result.data.labels.map(label => String(label).toLowerCase().trim());
                        const barThemes = normalizedLabels.map(label => statusTemplateMap[label] || 'secondary');
                        const backgroundColors = barThemes.map(theme => statusColorMap[theme].bg);
                        const borderColors = barThemes.map(theme => statusColorMap[theme].border);

                        // Label paparan paksi-x: buang perkataan "Aduan "
                        const displayLabels = result.data.labels.map(label =>
                            String(label).replace(/^Aduan\s+/i, '').trim()
                        );

                        // Create new chart
                        statusChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                //labels: result.data.labels,
                                labels: displayLabels,
                                datasets: [{
                                    label: 'Bilangan Tiket',
                                    data: result.data.counts,
                                    backgroundColor: backgroundColors,
                                    borderColor: borderColors,
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                layout: {
                                    padding: {
                                        top: window.isMonitoringMobile && window.isMonitoringMobile() ? 18 : 25,
                                        bottom: window.isMonitoringMobile && window.isMonitoringMobile() ? 4 : 8
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    title: {
                                        display: false
                                    },
                                    datalabels: window.getMonitoringBarDataLabelOptions ?
                                        window.getMonitoringBarDataLabelOptions() : {
                                            anchor: 'end',
                                            align: 'top',
                                            color: '#2c3e50',
                                            font: {
                                                weight: 'bold',
                                                size: window.getChartFont ? window.getChartFont(1) : 14
                                            },
                                            formatter: function(value) {
                                                return value;
                                            }
                                        }
                                },
                                scales: window.getMonitoringBarScaleOptions ?
                                    window.getMonitoringBarScaleOptions() : {
                                        y: {
                                            beginAtZero: true,
                                            ticks: {
                                                stepSize: 1,
                                                font: {
                                                    size: window.getChartFont ? window.getChartFont(0.85) : 12
                                                }
                                            }
                                        },
                                        x: {
                                            ticks: {
                                                font: {
                                                    size: window.getChartFont ? window.getChartFont(0.8) : 11
                                                }
                                            }
                                        }
                                    }
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading status chart:', error);
                });
        }

        // Initial load
        loadStatusChart();
        window.monitoringChartReloaders = window.monitoringChartReloaders || [];
        window.monitoringChartReloaders.push(loadStatusChart);

        // Refresh every 60 seconds
        setInterval(loadStatusChart, 60000);
    });
</script>
