<script>
let chartsInited = false;
function initCharts() {
  if (chartsInited) return;
  chartsInited = true;
  new Chart(document.getElementById('weeklyChart'), {
    type: 'bar',
    data: { labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'], datasets: [{ label: 'Rentals', data: [45,52,61,58,73,89,78], backgroundColor: '#22c55e', borderRadius: 6, borderSkipped: false }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { color: '#9ca3af' } }, y: { grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af' } } } }
  });
  new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: { labels: ['E-Scooter',"Lady's/Men's Bike",'Mountain Bike','City Bike','Kiddie Bikes'], datasets: [{ data: [48,68,89,72,40], backgroundColor: ['#8b5cf6','#3b82f6','#22c55e','#0ea5e9','#f59e0b'], borderWidth: 2, borderColor: '#fff' }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '60%' }
  });
  new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: { labels: ['Jan','Feb','Mar','Apr','May'], datasets: [
      { label: 'Revenue (₱)', data: [12400,15600,18900,22100,19800], borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,.1)', borderWidth: 2, pointBackgroundColor: '#22c55e', pointRadius: 4, fill: true, yAxisID: 'y' },
      { label: 'Rentals', data: [245,312,378,442,396], borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.08)', borderWidth: 2, pointBackgroundColor: '#3b82f6', pointRadius: 4, fill: true, yAxisID: 'y1' }
    ]},
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#374151', boxWidth: 10, padding: 16 } } }, scales: { x: { grid: { display: false }, ticks: { color: '#9ca3af' } }, y: { grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af' } }, y1: { position: 'right', grid: { display: false }, ticks: { color: '#9ca3af' } } } }
  });
  new Chart(document.getElementById('peakChart'), {
    type: 'bar',
    data: { labels: ['6AM','8AM','10AM','12PM','2PM','4PM','6PM','8PM'], datasets: [{ label: 'Rentals', data: [12,35,48,62,58,71,84,45], backgroundColor: '#22c55e', borderRadius: 6, borderSkipped: false }] },
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