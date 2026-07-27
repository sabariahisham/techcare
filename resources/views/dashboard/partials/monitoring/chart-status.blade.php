<div class="col-12 col-lg-4 d-flex align-items-stretch chart-card">
    <div class="card flex-fill d-flex flex-column" style="padding-left: 10px; padding-right: 10px;">
        <div class="card-header" style="flex-shrink: 0;">
            <h5 class="card-title mb-0">Graf Status Aduan</h5>
            <p class="card-subtitle text-muted mb-0">Data real-time status tiket</p>
        </div>
        <div class="card-body flex-grow-1"
            style="padding: 0.5rem; min-height: 0; display: flex; flex-direction: column; justify-content: center;">
            <div id="completed-status-card"
                class="d-flex align-items-center justify-content-between bg-success bg-opacity-10 rounded-3 p-3 mb-3"
                style="gap: 0.75rem;">
                <div class="d-flex align-items-center" style="gap: 0.75rem;">
                    <span class="badge rounded-pill bg-success text-white p-2">
                        <i class="bx bx-check-circle bx-sm"></i>
                    </span>
                    <div>
                        {{-- <div class="text-muted" style="font-size: 0.82rem;">Aduan Status Selesai</div> --}}
                        <div id="completed-status-count" class="h5 fw-bold mb-0">0 tiket</div>
                    </div>
                </div>
                <span class="badge rounded-pill bg-white text-success border border-success">Aduan Selesai</span>
            </div>
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
                            primary: {
                                bg: 'rgba(105, 108, 255, 0.8)',
                                border: 'rgba(105, 108, 255, 1)'
                            },
                            warning: {
                                bg: 'rgba(255, 171, 0, 0.8)',
                                border: 'rgba(255, 171, 0, 1)'
                            },
                            info: {
                                bg: 'rgba(3, 195, 236, 0.8)',
                                border: 'rgba(3, 195, 236, 1)'
                            },
                            danger: {
                                bg: 'rgba(255, 62, 29, 0.8)',
                                border: 'rgba(255, 62, 29, 1)'
                            },
                            secondary: {
                                bg: 'rgba(133, 146, 163, 0.8)',
                                border: 'rgba(133, 146, 163, 1)'
                            },
                            success: {
                                bg: 'rgba(113, 221, 55, 0.8)',
                                border: 'rgba(113, 221, 55, 1)'
                            }
                        };

                        const completedCount = result.data.completed_count ?? 0;
                        const completedCountElement = document.getElementById('completed-status-count');
                        if (completedCountElement) {
                            completedCountElement.textContent = `${completedCount} tiket`;
                        }

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

                        const normalizedLabels = result.data.labels.map(label => String(label).toLowerCase()
                            .trim());
                        const barThemes = normalizedLabels.map(label => statusTemplateMap[label] ||
                            'secondary');
                        const backgroundColors = barThemes.map(theme => statusColorMap[theme].bg);
                        const borderColors = barThemes.map(theme => statusColorMap[theme].border);

                        // Label paparan paksi-x: buang perkataan "Aduan "
                        const displayLabels = result.data.labels.map(label =>
                            // Label paparan paksi-x: buang perkataan "Aduan "    
                            //String(label).replace(/^Aduan\s+/i, '').trim()
                            //label paparan kekalkan perkataan "Aduan "
                            String(label).trim()
                        );

                        // Compute Y-axis step and suggested max so gridlines are reasonable.
                        // Choose a "nice" step (1,2,5 * 10^n) to produce ~5 ticks by default.
                        const maxCount = result.data.counts && result.data.counts.length ? Math.max(...
                            result.data.counts) : 0;

                        function computeNiceStep(maxValue, targetTicks = 5) {
                            if (!maxValue || maxValue <= 0) return 1;
                            const rawStep = Math.ceil(maxValue / targetTicks);
                            const magnitude = Math.pow(10, Math.floor(Math.log10(rawStep)));
                            const normalized = rawStep / magnitude;
                            let niceNorm;
                            if (normalized <= 1) niceNorm = 1;
                            else if (normalized <= 2) niceNorm = 2;
                            else if (normalized <= 5) niceNorm = 5;
                            else niceNorm = 10;
                            return niceNorm * magnitude;
                        }

                        const targetTicks = 5; // adjust to preference (4-6 recommended)
                        const stepSize = computeNiceStep(maxCount, targetTicks);
                        const suggestedMax = Math.ceil((maxCount || 1) / stepSize) * stepSize;

                        // Extra top padding so datalabels placed above bars are not clipped
                        const baseTopPadding = window.isMonitoringMobile && window.isMonitoringMobile() ?
                            18 : 25;
                        const topPaddingExtra = 20; // increase if labels still get clipped

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
                                        top: baseTopPadding + topPaddingExtra,
                                        bottom: window.isMonitoringMobile && window
                                            .isMonitoringMobile() ? 4 : 8
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
                                                size: window.getChartFont ? window.getChartFont(1) :
                                                    14
                                            },
                                            formatter: function(value) {
                                                return value;
                                            }
                                        }
                                },
                                // Obtain base scale options (may come from monitoring.js). Then override
                                // the Y-axis stepSize and suggestedMax so our inline settings take effect.
                                scales: (function() {
                                    const base = window.getMonitoringBarScaleOptions ?
                                        window.getMonitoringBarScaleOptions() : {
                                            y: {
                                                beginAtZero: true,
                                                ticks: {
                                                    stepSize: 1,
                                                    font: {
                                                        size: window.getChartFont ? window
                                                            .getChartFont(0.85) : 12
                                                    }
                                                }
                                            },
                                            x: {
                                                ticks: {
                                                    font: {
                                                        size: window.getChartFont ? window
                                                            .getChartFont(0.8) : 11
                                                    }
                                                }
                                            }
                                        };

                                    // Ensure object paths exist
                                    base.y = base.y || {};
                                    base.y.ticks = base.y.ticks || {};

                                    // Apply our overrides
                                    base.y.suggestedMax = suggestedMax;
                                    base.y.ticks.stepSize = stepSize;

                                    return base;
                                })()
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
