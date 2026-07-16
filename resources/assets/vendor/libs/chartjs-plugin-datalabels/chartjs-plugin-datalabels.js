import ChartDataLabels from 'chartjs-plugin-datalabels';

try {
  window.ChartDataLabels = ChartDataLabels;

  if (typeof window.Chart !== 'undefined') {
    window.Chart.register(ChartDataLabels);
  }
} catch (e) {}

export { ChartDataLabels };
