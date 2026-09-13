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

// beh aha mane?  wa man tay filter sa reports lagi 
// nvm nakita na d sa nako hilabtan kay hardcoded kapoy 
function filterReportsRange(range) {
  // TODO: wire to backend endpoint, e.g. fetch(`/admin/reports/data?range=${range}`)
  console.log('Reports range changed to', range);
}
// mana gi add na nako csv working 
function exportReports() {
  const rows = [];

  rows.push(['Reports Summary', '']);
  document.querySelectorAll('#page-reports .stat-card').forEach(card => {
    const value = card.querySelector('.stat-value').textContent.trim();
    const label = card.querySelector('.stat-label').textContent.trim();
    rows.push([label, value]);
  });

  rows.push(['', '']);

  rows.push(['Performance by Bike Type', '']);
  rows.push(['Bike Type', 'Total Rentals', 'Revenue', 'Avg. Duration', 'Utilization']);

  document.querySelectorAll('#reports-tbody tr').forEach(row => {
    const cells = row.querySelectorAll('td');
    const bikeType = cells[0].textContent.trim();
    const totalRentals = cells[1].textContent.trim();
    const revenue = cells[2].textContent.trim();
    const avgDuration = cells[3].textContent.trim();
    const utilization = cells[4].querySelector('span').textContent.trim();

    rows.push([bikeType, totalRentals, revenue, avgDuration, utilization]);
  });

  const csvContent = rows.map(row =>
    row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')
  ).join('\n');

  const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `reports-${new Date().toISOString().slice(0,10)}.csv`;
  link.click();
  URL.revokeObjectURL(url);

  showToast('Report exported successfully.');

  // TODO: wire to backend export route, e.g. window.location = '/admin/reports/export'
  console.log('Export reports clicked');
}
</script>