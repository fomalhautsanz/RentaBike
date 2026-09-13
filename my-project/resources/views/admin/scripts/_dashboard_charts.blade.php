<script>
// UPDATE: real data injected from DashboardController@index 
// no more hardcoded arrays.
const dashboardData = {
  weekly: @json($weeklyRentals ?? ['labels' => [], 'data' => []]),
  bikeTypes: @json($bikeTypeDistribution ?? []),
  revenueVsRentals: @json($revenueVsRentals ?? ['labels' => [], 'revenue' => [], 'rentals' => []]),
  peakHours: @json($peakHours ?? ['labels' => [], 'data' => []]),
};

let chartsInited = false;
function initCharts() {
  if (chartsInited) return;
  chartsInited = true;

  new Chart(document.getElementById('weeklyChart'), {
    type: 'bar',
    data: { labels: dashboardData.weekly.labels, datasets: [{ label: 'Rentals', data: dashboardData.weekly.data, backgroundColor: '#22c55e', borderRadius: 6, borderSkipped: false }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { color: '#9ca3af' } }, y: { grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af' } } } }
  });

  const bikeTypeLabels = Object.keys(dashboardData.bikeTypes);
  const bikeTypeValues = Object.values(dashboardData.bikeTypes);
  const bikeTypeColors = ['#8b5cf6','#3b82f6','#22c55e','#0ea5e9','#f59e0b','#ef4444','#14b8a6'];
  new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: { labels: bikeTypeLabels, datasets: [{ data: bikeTypeValues, backgroundColor: bikeTypeColors.slice(0, bikeTypeLabels.length), borderWidth: 2, borderColor: '#fff' }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '60%' }
  });

  new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: { labels: dashboardData.revenueVsRentals.labels, datasets: [
      { label: 'Revenue (₱)', data: dashboardData.revenueVsRentals.revenue, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,.1)', borderWidth: 2, pointBackgroundColor: '#22c55e', pointRadius: 4, fill: true, yAxisID: 'y' },
      { label: 'Rentals', data: dashboardData.revenueVsRentals.rentals, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.08)', borderWidth: 2, pointBackgroundColor: '#3b82f6', pointRadius: 4, fill: true, yAxisID: 'y1' }
    ]},
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#374151', boxWidth: 10, padding: 16 } } }, scales: { x: { grid: { display: false }, ticks: { color: '#9ca3af' } }, y: { grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af' } }, y1: { position: 'right', grid: { display: false }, ticks: { color: '#9ca3af' } } } }
  });

  new Chart(document.getElementById('peakChart'), {
    type: 'bar',
    data: { labels: dashboardData.peakHours.labels, datasets: [{ label: 'Rentals', data: dashboardData.peakHours.data, backgroundColor: '#22c55e', borderRadius: 6, borderSkipped: false }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { color: '#9ca3af' } }, y: { grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af' } } } }
  });
}

// Auto-init charts since we start on dashboard
document.addEventListener('DOMContentLoaded', () => initCharts());

// Re-init charts when navigating to dashboard
const _origNav = window.nav;
window.nav = function(id, btn) {
  _origNav(id, btn);
  if (id === 'dashboard') initCharts();
};
</script>