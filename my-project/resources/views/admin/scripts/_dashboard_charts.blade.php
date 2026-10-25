{{-- ExcelJS library (remove this line if your layout already loads it) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>

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

// ── Bike Inventory export (styled .xlsx) ─────────────────────────────────
// all bikes from the controller ($bikes), so the export is not limited to what is visible on screen
const bikeInventoryData = @json($bikes ?? []);

async function exportBikesExcel() {
  const wb = new ExcelJS.Workbook();
  const ws = wb.addWorksheet('Bike Inventory');

  ws.columns = [
    { width: 22 }, { width: 30 }, { width: 18 }, { width: 18 }, { width: 18 },
  ];

  const thin = { style: 'thin' };
  const border = { top: thin, left: thin, bottom: thin, right: thin };
  const fill = (argb) => ({ type: 'pattern', pattern: 'solid', fgColor: { argb } });

  // Title banner
  ws.mergeCells('A1:E1');
  const title = ws.getCell('A1');
  title.value = 'RENTABIKE';
  title.font = { bold: true, size: 18, color: { argb: 'FF1F3864' } };
  title.fill = fill('FFFFFF00');
  title.alignment = { horizontal: 'center', vertical: 'middle' };
  ws.getRow(1).height = 32;

  // Report title
  ws.mergeCells('A2:E2');
  const sub = ws.getCell('A2');
  sub.value = 'BIKE INVENTORY REPORT';
  sub.font = { bold: true, size: 12, color: { argb: 'FF1F3864' } };
  sub.fill = fill('FFFCE4D6');
  sub.alignment = { horizontal: 'center' };

  // Date generated
  ws.mergeCells('A3:E3');
  const dateCell = ws.getCell('A3');
  dateCell.value = new Date().toLocaleString('en-US', {
    timeZone: 'Asia/Manila',
    month: 'long', day: 'numeric', year: 'numeric',
    hour: '2-digit', minute: '2-digit', hour12: true
  });
  dateCell.font = { italic: true, size: 11 };
  dateCell.alignment = { horizontal: 'center' };

  // Header row (row 5)
  const headers = ['QR Code', 'Model · Make', 'Type', 'Status', 'Condition'];
  const headerColors = ['FF00B0F0', 'FFD9D2C0', 'FFF4B183', 'FF92D050', 'FF92D050'];
  const headerRow = ws.getRow(5);
  headers.forEach((h, i) => {
    const c = headerRow.getCell(i + 1);
    c.value = h;
    c.font = { bold: true };
    c.fill = fill(headerColors[i]);
    c.border = border;
    c.alignment = { horizontal: 'center', vertical: 'middle' };
  });
  headerRow.height = 26;

  // Data rows
  let r = 6;
  bikeInventoryData.forEach(bike => {
    const values = [bike.qr_code, bike.name, bike.type, bike.status, bike.condition];
    const row = ws.getRow(r++);

    values.forEach((v, i) => {
      const c = row.getCell(i + 1);
      c.value = v ?? '';
      c.border = border;
      c.alignment = { vertical: 'middle', horizontal: i >= 3 ? 'center' : 'left' };
    });
    row.getCell(1).font = { bold: true };

    // Color the status cell
    const statusCell = row.getCell(4);
    if (bike.status === 'Available') statusCell.fill = fill('FFC6EFCE');
    else if (bike.status === 'Rented') statusCell.fill = fill('FFBDD7EE');
    else if (bike.status === 'Maintenance') statusCell.fill = fill('FFFFC7CE');
  });

  // Download
  const buffer = await wb.xlsx.writeBuffer();
  const blob = new Blob([buffer], {
    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `bike-inventory-${new Date().toISOString().slice(0, 10)}.xlsx`;
  link.click();
  URL.revokeObjectURL(url);

  showToast('Bike inventory exported successfully.');
}
</script>