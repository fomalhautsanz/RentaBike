<script>
let reportsChartsInited = false;
let reportsRevenueChart, reportsPeakChart;
let reportsData = null;      // latest data from the server, also used by the export
let reportsRequestId = 0;    // para ma-ignore ang daan nga response kung ni-ilis og range

function initReportsCharts() {
  if (reportsChartsInited) return;
  reportsChartsInited = true;

  reportsRevenueChart = new Chart(document.getElementById('reportsRevenueChart'), {
    type: 'line',
    data: {
      labels: [],
      datasets: [
        { label: 'Revenue (₱)', data: [], borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,.1)', borderWidth: 2, pointBackgroundColor: '#22c55e', pointRadius: 4, fill: true, yAxisID: 'y' },
        { label: 'Rentals', data: [], borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.08)', borderWidth: 2, pointBackgroundColor: '#3b82f6', pointRadius: 4, fill: true, yAxisID: 'y1' }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { labels: { color: '#374151', boxWidth: 10, padding: 16 } } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#9ca3af' } },
        y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af' } },
        y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { color: '#9ca3af', precision: 0 } }
      }
    }
  });

  reportsPeakChart = new Chart(document.getElementById('reportsPeakChart'), {
    type: 'bar',
    data: {
      labels: [],
      datasets: [{ label: 'Rentals', data: [], backgroundColor: '#22c55e', borderRadius: 6, borderSkipped: false }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#9ca3af' } },
        y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { color: '#9ca3af', precision: 0 } }
      }
    }
  });
}

// Re-init charts when navigating to the Reports & Analytics tab
const _origNavReports = window.nav;
window.nav = function(id, btn) {
  _origNavReports(id, btn);
  if (id === 'reports') {
    initReportsCharts();
    filterReportsRange(document.getElementById('reports-range').value);
  }
};

async function filterReportsRange(range) {
  const requestId = ++reportsRequestId;

  try {
    const res = await fetch(`{{ route('admin.reports.data') }}?range=${encodeURIComponent(range)}`, {
      headers: { Accept: 'application/json' },
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const data = await res.json();
    if (requestId !== reportsRequestId) return;

    const select = document.getElementById('reports-range');
    data.rangeLabel = select.options[select.selectedIndex].text;
    reportsData = data;
    renderReports(data);
  } catch (error) {
    if (requestId === reportsRequestId) showToast('Could not load report data.');
  }
}

function renderReports(d) {
  setReportStat('revenue', `₱${Math.round(d.stats.revenue.value).toLocaleString()}`, d.stats.revenue.change);
  setReportStat('rentals', d.stats.rentals.value.toLocaleString(), d.stats.rentals.change);
  setReportStat('customers', d.stats.customers.value.toLocaleString(), d.stats.customers.change);
  setReportStat('duration', `${d.stats.duration.value}h`, d.stats.duration.change);

  reportsRevenueChart.data.labels = d.trend.labels;
  reportsRevenueChart.data.datasets[0].data = d.trend.revenue;
  reportsRevenueChart.data.datasets[1].data = d.trend.rentals;
  reportsRevenueChart.update();

  reportsPeakChart.data.labels = d.peak.labels;
  reportsPeakChart.data.datasets[0].data = d.peak.data;
  reportsPeakChart.update();

  const tbody = document.getElementById('reports-tbody');
  tbody.innerHTML = '';

  if (!d.bikeTypes.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#6b7280">No bikes found.</td></tr>';
    return;
  }

  d.bikeTypes.forEach(row => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td style="font-weight:500"></td>
      <td>${row.rentals.toLocaleString()}</td>
      <td>₱${Math.round(row.revenue).toLocaleString()}</td>
      <td>${row.avg_duration}h</td>
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <div style="flex:1;height:6px;background:#f3f4f6;border-radius:4px;overflow:hidden;max-width:100px">
            <div style="height:100%;background:#22c55e;width:${row.utilization}%"></div>
          </div>
          <span style="font-size:12px;color:#6b7280">${row.utilization}%</span>
        </div>
      </td>`;
    tr.cells[0].textContent = row.type;
    tbody.appendChild(tr);
  });
}

function setReportStat(key, value, change) {
  document.getElementById(`report-${key}`).textContent = value;

  const badge = document.getElementById(`report-${key}-change`);
  badge.textContent = change ? `${change > 0 ? '↑' : '↓'} ${Math.abs(change)}%` : '0%';
}

// export reports to styled excel (.xlsx)
async function exportReportsExcel() {
  if (!reportsData) {
    showToast('Report data is still loading.');
    return;
  }

  const d = reportsData;
  const wb = new ExcelJS.Workbook();
  const ws = wb.addWorksheet('Reports Summary');

  ws.columns = [
    { width: 26 }, { width: 18 }, { width: 18 }, { width: 18 }, { width: 16 },
  ];

  const thin = { style: 'thin' };
  const border = { top: thin, left: thin, bottom: thin, right: thin };
  const fill = (argb) => ({ type: 'pattern', pattern: 'solid', fgColor: { argb } });
  const peso = '"₱"#,##0.00';

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
  sub.value = `REPORTS SUMMARY REPORT (${d.rangeLabel.toUpperCase()})`;
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

  const addSection = (text, argb) => {
    ws.mergeCells(`A${r}:E${r}`);
    const cell = ws.getCell(`A${r}`);
    cell.value = text;
    cell.font = { bold: true, size: 12 };
    cell.fill = fill(argb);
    cell.alignment = { horizontal: 'left', vertical: 'middle' };
    r++;
  };

  const addHeaders = (headers, color) => {
    const headerRow = ws.getRow(r++);
    headers.forEach((h, i) => {
      const c = headerRow.getCell(i + 1);
      c.value = h;
      c.font = { bold: true };
      c.fill = fill(color);
      c.border = border;
      c.alignment = { horizontal: 'center', vertical: 'middle' };
    });
    headerRow.height = 24;
  };

  // formats: optional number format per column
  const addRow = (values, formats = []) => {
    const row = ws.getRow(r++);
    values.forEach((v, i) => {
      const c = row.getCell(i + 1);
      c.value = v;
      c.border = border;
      c.alignment = { vertical: 'middle', horizontal: i === 0 ? 'left' : 'center' };
      if (formats[i]) c.numFmt = formats[i];
    });
    row.getCell(1).font = { bold: true };
  };

  const changeText = (change) => change ? `${change > 0 ? '+' : ''}${change}%` : '0%';

  // ── Section 1: Reports Summary (stat cards) ──
  addSection('Reports Summary', 'FF00B0F0');
  addHeaders(['Metric', 'Value', 'Change vs Previous'], 'FFD9D2C0');
  addRow(['Total Revenue', d.stats.revenue.value, changeText(d.stats.revenue.change)], [null, peso]);
  addRow(['Total Rentals', d.stats.rentals.value, changeText(d.stats.rentals.change)]);
  addRow(['Unique Customers', d.stats.customers.value, changeText(d.stats.customers.change)]);
  addRow(['Avg Rental Duration (hrs)', d.stats.duration.value, changeText(d.stats.duration.change)]);
  r++; // blank row

  // ── Section 2: Performance by Bike Type ──
  addSection('Performance by Bike Type', 'FF92D050');
  addHeaders(['Bike Type', 'Total Rentals', 'Revenue', 'Avg. Duration (hrs)', 'Utilization'], 'FFD9D2C0');
  d.bikeTypes.forEach(row => {
    addRow([row.type, row.rentals, row.revenue, row.avg_duration, row.utilization / 100], [null, null, peso, null, '0%']);
  });
  r++; // blank row

  // ── Section 3: Revenue & Rentals Trend ──
  addSection('Revenue & Rentals Trend', 'FFF4B183');
  addHeaders(['Period', 'Revenue', 'Rentals'], 'FFD9D2C0');
  d.trend.labels.forEach((label, i) => {
    addRow([label, d.trend.revenue[i], d.trend.rentals[i]], [null, peso]);
  });
  r++; // blank row

  // ── Section 4: Peak Rental Hours ──
  addSection('Peak Rental Hours (2-hour blocks)', 'FFFFD966');
  addHeaders(['Starting At', 'Rentals'], 'FFD9D2C0');
  d.peak.labels.forEach((label, i) => {
    addRow([label, d.peak.data[i]]);
  });

  // Download
  const buffer = await wb.xlsx.writeBuffer();
  const blob = new Blob([buffer], {
    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = `reports-${document.getElementById('reports-range').value}-${new Date().toISOString().slice(0, 10)}.xlsx`;
  link.click();
  URL.revokeObjectURL(url);

  showToast('Report exported successfully.');
}
</script>
