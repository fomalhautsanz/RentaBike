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

// export reports to styled excel (.xlsx)
async function exportReportsExcel() {
  const wb = new ExcelJS.Workbook();
  const ws = wb.addWorksheet('Reports Summary');

  ws.columns = [
    { width: 26 }, { width: 18 }, { width: 18 }, { width: 18 }, { width: 16 },
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
  sub.value = 'REPORTS SUMMARY REPORT';
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

  let r = 5;

  // ── Section 1: Reports Summary (stat cards) ──
  ws.mergeCells(`A${r}:E${r}`);
  const sec1 = ws.getCell(`A${r}`);
  sec1.value = 'Reports Summary';
  sec1.font = { bold: true, size: 12 };
  sec1.fill = fill('FF00B0F0');
  sec1.alignment = { horizontal: 'left', vertical: 'middle' };
  r++;

  document.querySelectorAll('#page-reports .stat-card').forEach(card => {
    const value = card.querySelector('.stat-value').textContent.trim();
    const label = card.querySelector('.stat-label').textContent.trim();

    const row = ws.getRow(r++);
    row.getCell(1).value = label;
    row.getCell(1).font = { bold: true };
    row.getCell(2).value = value;
    row.getCell(2).alignment = { horizontal: 'right' };
    row.getCell(1).border = border;
    row.getCell(2).border = border;
  });

  r++; // blank row

  // ── Section 2: Performance by Bike Type ──
  ws.mergeCells(`A${r}:E${r}`);
  const sec2 = ws.getCell(`A${r}`);
  sec2.value = 'Performance by Bike Type';
  sec2.font = { bold: true, size: 12 };
  sec2.fill = fill('FF92D050');
  sec2.alignment = { horizontal: 'left', vertical: 'middle' };
  r++;

  const headers = ['Bike Type', 'Total Rentals', 'Revenue', 'Avg. Duration', 'Utilization'];
  const headerColors = ['FFD9D2C0', 'FFD9D2C0', 'FFF4B183', 'FFF4B183', 'FFD9D2C0'];
  const headerRow = ws.getRow(r++);
  headers.forEach((h, i) => {
    const c = headerRow.getCell(i + 1);
    c.value = h;
    c.font = { bold: true };
    c.fill = fill(headerColors[i]);
    c.border = border;
    c.alignment = { horizontal: 'center', vertical: 'middle' };
  });
  headerRow.height = 24;

  document.querySelectorAll('#reports-tbody tr').forEach(tr => {
    const cells = tr.querySelectorAll('td');
    const values = [
      cells[0].textContent.trim(),
      cells[1].textContent.trim(),
      cells[2].textContent.trim(),
      cells[3].textContent.trim(),
      cells[4].querySelector('span').textContent.trim(),
    ];

    const row = ws.getRow(r++);
    values.forEach((v, i) => {
      const c = row.getCell(i + 1);
      c.value = v;
      c.border = border;
      c.alignment = { vertical: 'middle', horizontal: i === 0 ? 'left' : 'center' };
    });
    row.getCell(1).font = { bold: true };
  });

  // Download
  const buffer = await wb.xlsx.writeBuffer();
  const blob = new Blob([buffer], {
    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `reports-${new Date().toISOString().slice(0, 10)}.xlsx`;
  link.click();
  URL.revokeObjectURL(url);

  showToast('Report exported successfully.');
}
</script>