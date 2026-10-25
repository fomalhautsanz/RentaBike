<script>
let reportsChartsInited = false;
function initReportsCharts() {
  if (reportsChartsInited) return;
  reportsChartsInited = true;

  new Chart(document.getElementById('reportsRevenueChart'), {
    type: 'line',
    data: {
      labels: ['Jan','Feb','Mar','Apr','May'],
      datasets: [
        { label: 'Revenue (₱)', data: [14000,18000,22000,23500,20500], borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,.1)', borderWidth: 2, pointBackgroundColor: '#22c55e', pointRadius: 4, fill: true, yAxisID: 'y' },
        { label: 'Rentals', data: [260,320,400,410,350], borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.08)', borderWidth: 2, pointBackgroundColor: '#3b82f6', pointRadius: 4, fill: true, yAxisID: 'y1' }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { labels: { color: '#374151', boxWidth: 10, padding: 16 } } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#9ca3af' } },
        y: { grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af' } },
        y1: { position: 'right', grid: { display: false }, ticks: { color: '#9ca3af' } }
      }
    }
  });

  new Chart(document.getElementById('reportsPeakChart'), {
    type: 'bar',
    data: {
      labels: ['6AM','8AM','10AM','12PM','2PM','4PM','6PM','8PM'],
      datasets: [{ label: 'Rentals', data: [12,35,48,62,58,71,84,45], backgroundColor: '#22c55e', borderRadius: 6, borderSkipped: false }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#9ca3af' } },
        y: { grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af' } }
      }
    }
  });
}

// Re-init charts when navigating to the Reports & Analytics tab
const _origNavReports = window.nav;
window.nav = function(id, btn) {
  _origNavReports(id, btn);
  if (id === 'reports') initReportsCharts();
};

function filterReportsRange(range) {
  // TODO: wire to backend endpoint, e.g. fetch(`/admin/reports/data?range=${range}`)
  console.log('Reports range changed to', range);
}

function exportReports() {
  // TODO: wire to backend export route, e.g. window.location = '/admin/reports/export'
  console.log('Export reports clicked');
}
</script>