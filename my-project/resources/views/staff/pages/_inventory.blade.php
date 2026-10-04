{{-- INVENTORY SCREEN --}}
<style>
  .inventory-open-button{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border:1px solid var(--green-600);border-radius:var(--radius-md);background:var(--green-600);color:#fff;font-size:13px;font-weight:700;text-decoration:none;cursor:pointer;box-shadow:0 2px 8px rgba(22,163,74,.2)}
  #inventory .inventory-tools{display:flex;gap:8px;margin-bottom:8px;align-items:stretch}
  #inventory .inventory-search{display:flex;align-items:center;gap:8px;min-width:0;flex:1;padding:9px 12px;border:1px solid var(--gray-200);border-radius:12px;background:var(--white)}
  #inventory .inventory-search svg{width:16px;height:16px;flex-shrink:0;color:var(--gray-500)}
  #inventory .inventory-search input{width:100%;min-width:0;border:0;outline:0;background:transparent;color:var(--gray-900);font:inherit;font-size:13px}
  #inventory .inventory-filter-wrap{position:relative;flex-shrink:0}
  #inventory .inventory-filter-button{display:flex;align-items:center;gap:6px;height:100%;padding:9px 12px;border:1px solid var(--gray-200);border-radius:12px;background:var(--white);color:var(--gray-800);font-size:13px;font-weight:700;cursor:pointer}
  #inventory .inventory-filter-button.active{border-color:var(--green-600);background:#e5f7ec;color:var(--green-600)}
  #inventory .inventory-filter-button svg{width:15px;height:15px}
  #inventory .filter-count{padding:1px 6px;border-radius:999px;background:var(--green-600);color:#fff;font-size:10px}
  #inventory .inventory-filter-popover{position:absolute;top:calc(100% + 8px);right:0;z-index:20;width:min(280px,calc(100vw - 40px));padding:12px;border:1px solid var(--gray-200);border-radius:12px;background:var(--white);box-shadow:0 12px 32px rgba(20,20,40,.16)}
  #inventory .filter-quick-row{display:flex;gap:8px}
  #inventory .filter-quick-button{display:flex;align-items:center;justify-content:center;gap:5px;min-width:0;flex:1;padding:9px 7px;border:1px solid var(--gray-200);border-radius:9px;background:#fafafc;color:var(--gray-800);font-size:12px;font-weight:700;cursor:pointer}
  #inventory .filter-quick-button.active{border-color:var(--green-600);background:#e5f7ec;color:var(--green-600)}
  #inventory .filter-quick-button span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  #inventory .filter-option-list{max-height:190px;margin-top:8px;overflow-y:auto;border:1px solid var(--gray-200);border-radius:9px}
  #inventory .filter-option{display:flex;width:100%;align-items:center;justify-content:space-between;padding:9px 10px;border:0;border-bottom:1px solid var(--gray-100);background:var(--white);color:var(--gray-800);font-size:12.5px;text-align:left;cursor:pointer}
  #inventory .filter-option:last-child{border-bottom:0}
  #inventory .filter-option.selected{background:#e5f7ec;color:var(--green-600);font-weight:700}
  #inventory .filter-option svg{width:14px;height:14px;visibility:hidden;color:var(--green-600)}
  #inventory .filter-option.selected svg{visibility:visible}
  #inventory .filter-actions{display:flex;gap:8px;margin-top:12px}
  #inventory .filter-action{flex:1;padding:9px 8px;border:1px solid var(--gray-200);border-radius:9px;background:var(--white);color:var(--gray-600);font-size:12.5px;font-weight:700;cursor:pointer}
  #inventory .filter-action.confirm{border-color:var(--green-600);background:var(--green-600);color:#fff}
  #inventory .inventory-result-count{margin:0 2px 8px;color:var(--gray-500);font-size:11px}
  #inventory .inventory-category{margin-bottom:10px;overflow:hidden;border:1px solid var(--gray-200);border-radius:14px;background:var(--white);box-shadow:var(--shadow-sm)}
  #inventory .inventory-category[hidden],#inventory .inventory-bike-row[hidden],#inventory .inventory-empty[hidden],#inventory .inventory-filter-popover[hidden],#inventory .filter-option-list[hidden]{display:none!important}
  #inventory .inventory-category-heading{display:flex;align-items:center;gap:10px;padding:13px 14px;cursor:pointer;list-style:none}
  #inventory .inventory-category-heading::-webkit-details-marker{display:none}
  #inventory .inventory-category-heading::marker{content:''}
  #inventory .inventory-category-icon{display:flex;width:36px;height:36px;flex-shrink:0;align-items:center;justify-content:center;border-radius:9px;background:#e5f7ec;color:var(--green-600)}
  #inventory .inventory-category-icon svg{width:20px;height:20px}
  #inventory .inventory-category-title{flex:1;min-width:0;color:var(--gray-900);font-size:14px;font-weight:700}
  #inventory .inventory-category-counts{display:flex;align-items:center;gap:9px;color:var(--gray-800);font-size:12px;font-weight:700}
  #inventory .inventory-status-total{display:inline-flex;align-items:center;gap:4px}
  #inventory .inventory-status-dot{width:8px;height:8px;border-radius:50%}
  #inventory .inventory-status-dot.available{background:#1f9d55}
  #inventory .inventory-status-dot.rented{background:#2f6fed}
  #inventory .inventory-status-dot.maintenance{background:#b8790a}
  #inventory .inventory-category-chevron{width:17px;height:17px;flex-shrink:0;color:var(--gray-400);transition:transform .16s ease}
  #inventory .inventory-category[open] .inventory-category-chevron{transform:rotate(180deg)}
  #inventory .inventory-category-list{padding:0 12px 8px}
  #inventory .inventory-category-list .inventory-bike-row{border-top:1px solid var(--gray-100)}
  #inventory .inventory-category-list .inventory-bike-row:last-child{border-bottom:0}
  #inventory .inventory-bike-row{gap:9px;padding:11px 12px}
  #inventory .inventory-bike-icon{display:flex;width:34px;height:34px;flex-shrink:0;align-items:center;justify-content:center;border-radius:9px}
  #inventory .inventory-bike-icon.available{background:#e5f7ec;color:#1f9d55}
  #inventory .inventory-bike-icon.rented{background:#e8effe;color:#2f6fed}
  #inventory .inventory-bike-icon.maintenance{background:#fdf1de;color:#b8790a}
  #inventory .inventory-bike-icon svg{width:19px;height:19px}
  #inventory .inventory-bike-desc{flex:1;min-width:0}
  #inventory .inventory-bike-name{overflow:hidden;color:var(--gray-500);font-size:11px;text-overflow:ellipsis;white-space:nowrap}
  #inventory .inventory-condition{flex-shrink:0;padding:4px 7px;border-radius:999px;background:#f1f1f5;color:var(--gray-700);font-size:10px;font-weight:700;white-space:nowrap}
  #inventory .inventory-bike-row .badge{flex-shrink:0;font-size:10px}
  #inventory .inventory-empty{padding:26px 12px;color:var(--gray-500);font-size:13px;text-align:center}
  @media(max-width:380px){#inventory .inventory-bike-row{gap:6px;padding:10px 8px}#inventory .inventory-condition{padding:4px 5px;font-size:9px}#inventory .inventory-bike-row .badge{font-size:9px}}
</style>

<section class="screen" id="inventory">
  <div class="page-header">
    <button class="back-btn" onclick="goTo('home')" aria-label="Back to home">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <h2>Bike Inventory</h2>
    <div style="display:flex;gap:6px;margin-left:auto;align-items:center">
      <a href="{{ route('staff.export') }}" style="display:inline-flex;align-items:center;gap:5px;padding:8px 12px;background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-md);font-size:12.5px;font-weight:600;color:var(--gray-700);text-decoration:none;box-shadow:var(--shadow-sm)">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export
      </a>
      @if($canAddInventory ?? false)
        <button type="button" onclick="goTo('inventory-create')" style="display:inline-flex;align-items:center;gap:5px;padding:8px 12px;background:var(--green-600);border:none;border-radius:var(--radius-md);font-size:12.5px;font-weight:600;color:#fff;cursor:pointer;box-shadow:0 2px 8px rgba(22,163,74,.25)">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Bike
        </button>
      @endif
    </div>
  </div>

  <div class="content">
    @if(!($canEditInventory ?? false) && !($canDeleteInventory ?? false) && !($canAddInventory ?? false))
      <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:var(--radius-md);padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;gap:8px">
        <svg width="15" height="15" fill="none" stroke="#2563eb" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span style="font-size:12px;color:#1d4ed8;font-weight:500">You have view-only access to this inventory.</span>
      </div>
    @endif

    <div class="inventory-tools">
      <label class="inventory-search">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input id="inventorySearch" type="search" placeholder="Search by ID, model, or type..." autocomplete="off" aria-label="Search inventory">
      </label>
      <div class="inventory-filter-wrap" id="inventoryFilterWrap">
        <button type="button" class="inventory-filter-button" id="inventoryFilterButton" aria-expanded="false" aria-controls="inventoryFilterPopover">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
          Filter <span class="filter-count" id="inventoryFilterCount" hidden>0</span>
        </button>
        <div class="inventory-filter-popover" id="inventoryFilterPopover" hidden>
          <div class="filter-quick-row">
            <button type="button" class="filter-quick-button" id="inventoryTypeButton" aria-expanded="false"><span id="inventoryTypeLabel">Type</span><svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
            <button type="button" class="filter-quick-button" id="inventoryStatusButton" aria-expanded="false"><span id="inventoryStatusLabel">Status</span><svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
          </div>
          <div class="filter-option-list" id="inventoryFilterOptions" role="listbox" hidden></div>
          <div class="filter-actions">
            <button type="button" class="filter-action" id="inventoryClearFilters">Clear</button>
            <button type="button" class="filter-action confirm" id="inventoryApplyFilters">Confirm</button>
          </div>
        </div>
      </div>
    </div>

    <p class="inventory-result-count" id="inventoryResultCount" aria-live="polite"></p>
    <div id="inventoryList">
      @forelse($bikeCategories as $category => $bikes)
        @php
          $categoryAvailable = $bikes->where('status', 'available')->count();
          $categoryRented = $bikes->where('status', 'rented')->count();
          $categoryMaintenance = $bikes->whereIn('status', ['repair', 'maintenance'])->count();
        @endphp
        <details class="inventory-category" data-inventory-category data-type="{{ $category }}">
          <summary class="inventory-category-heading">
            <span class="inventory-category-icon" aria-hidden="true">
              <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="3.2"/><circle cx="18.5" cy="17.5" r="3.2"/><path d="M15 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2ZM12 17.5V14l-3-3 4-3 2 3h3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="inventory-category-title">{{ $category }}</span>
            <span class="inventory-category-counts" aria-label="{{ count($bikes) }} bikes: {{ $categoryAvailable }} available, {{ $categoryRented }} rented, {{ $categoryMaintenance }} maintenance">
              <span class="inventory-status-total"><span class="inventory-status-dot available"></span>{{ $categoryAvailable }}</span>
              <span class="inventory-status-total"><span class="inventory-status-dot rented"></span>{{ $categoryRented }}</span>
              <span class="inventory-status-total"><span class="inventory-status-dot maintenance"></span>{{ $categoryMaintenance }}</span>
            </span>
            <svg class="inventory-category-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </summary>
          <div class="inventory-category-list inv-list">
        @foreach($bikes as $bike)
          @php
            $inventoryStatus = strtolower($bike->status ?? '');
            $inventoryStatusLabel = match($inventoryStatus) {
              'available' => 'Available',
              'rented' => 'Rented',
              'repair', 'maintenance' => 'Maintenance',
              default => ucfirst($inventoryStatus),
            };
            $inventoryStatusClass = match($inventoryStatus) {
              'available' => 'badge-green',
              'rented' => 'badge-blue',
              'repair', 'maintenance' => 'badge-orange',
              default => 'badge-gray',
            };
            $inventoryIconClass = match($inventoryStatus) {
              'available' => 'available',
              'rented' => 'rented',
              'repair', 'maintenance' => 'maintenance',
              default => 'maintenance',
            };
            $inventoryCondition = strtolower($bike->condition ?? '');
            $inventoryConditionLabel = match($inventoryCondition) {
              'good' => 'Good',
              'missing' => 'Missing',
              'repair' => 'Needs Repair',
              default => ucfirst($inventoryCondition),
            };
            $inventorySearch = strtolower(($bike->qr_code ?? '') . ' ' . ($bike->model ?? '') . ' ' . ($bike->make ?? '') . ' ' . $category);
          @endphp
          <div class="inv-row inventory-bike-row" data-inventory-row data-type="{{ $category }}" data-status="{{ in_array($inventoryStatus, ['repair', 'maintenance'], true) ? 'Maintenance' : $inventoryStatusLabel }}" data-search="{{ $inventorySearch }}">
            <div class="inventory-bike-icon {{ $inventoryIconClass }}" aria-hidden="true">
              <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="5.5" cy="17.5" r="3.2"/><circle cx="18.5" cy="17.5" r="3.2"/><path d="M15 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2ZM12 17.5V14l-3-3 4-3 2 3h3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="inventory-bike-desc">
              <div class="inv-row-id">{{ $bike->qr_code }}</div>
              <div class="inventory-bike-name">{{ $bike->model }} · {{ $category }} · {{ $bike->make }}</div>
            </div>
            <span class="inventory-condition">{{ $inventoryConditionLabel }}</span>
            <span class="badge {{ $inventoryStatusClass }}">{{ $inventoryStatusLabel }}</span>
            @if(($canEditInventory ?? false) || ($canDeleteInventory ?? false))
              <div style="display:flex;gap:4px;flex-shrink:0">
                @if($canEditInventory ?? false)
                  <button type="button" onclick="event.stopPropagation(); openBikeAction('edit', { id: this.dataset.bikeId, qrCode: this.dataset.qrCode, model: this.dataset.model, make: this.dataset.make, type: this.dataset.type, condition: this.dataset.condition })" data-bike-id="{{ $bike->qr_code }}" data-qr-code="{{ $bike->qr_code }}" data-model="{{ $bike->model }}" data-make="{{ $bike->make }}" data-type="{{ $bike->bike_type }}" data-condition="{{ $inventoryConditionLabel }}" class="action-btn" style="width:30px;height:30px" title="Edit {{ $bike->qr_code }}" aria-label="Edit {{ $bike->qr_code }}">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                @endif
                @if(($canDeleteInventory ?? false) && $bike->status !== 'rented')
                  <button type="button" onclick="event.stopPropagation(); openBikeAction('delete', { id: this.dataset.bikeId })" data-bike-id="{{ $bike->qr_code }}" class="action-btn" style="width:30px;height:30px;color:#ef4444" title="Delete {{ $bike->qr_code }}" aria-label="Delete {{ $bike->qr_code }}">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                  </button>
                @endif
              </div>
            @endif
          </div>
        @endforeach
          </div>
        </details>
      @empty
        <div class="inventory-empty" data-no-bikes>No bikes have been added yet.</div>
      @endforelse
      <div class="inventory-empty" id="inventoryNoResults" hidden>No bikes match this search or filter.</div>
    </div>
  </div>
</section>

<script>
(() => {
  const inventory = document.getElementById('inventory');
  const rows = [...inventory.querySelectorAll('[data-inventory-row]')];
  const categories = [...inventory.querySelectorAll('[data-inventory-category]')];
  const searchInput = document.getElementById('inventorySearch');
  const filterWrap = document.getElementById('inventoryFilterWrap');
  const filterButton = document.getElementById('inventoryFilterButton');
  const filterPopover = document.getElementById('inventoryFilterPopover');
  const filterCount = document.getElementById('inventoryFilterCount');
  const typeButton = document.getElementById('inventoryTypeButton');
  const statusButton = document.getElementById('inventoryStatusButton');
  const typeLabel = document.getElementById('inventoryTypeLabel');
  const statusLabel = document.getElementById('inventoryStatusLabel');
  const optionsList = document.getElementById('inventoryFilterOptions');
  const resultCount = document.getElementById('inventoryResultCount');
  const noResults = document.getElementById('inventoryNoResults');
  const types = [...new Set(rows.map(row => row.dataset.type))].sort((left, right) => left.localeCompare(right));
  const statuses = ['Available', 'Rented', 'Maintenance'];
  let appliedType = null;
  let appliedStatus = null;
  let draftType = null;
  let draftStatus = null;
  let openList = null;

  function renderRows() {
    const query = searchInput.value.trim().toLowerCase();
    let visibleCount = 0;
    rows.forEach(row => {
      const visible = (!appliedType || row.dataset.type === appliedType)
        && (!appliedStatus || row.dataset.status === appliedStatus)
        && (!query || row.dataset.search.includes(query));
      row.hidden = !visible;
      if (visible) visibleCount += 1;
    });
    categories.forEach(category => {
      const categoryRows = [...category.querySelectorAll('[data-inventory-row]')];
      const visibleRows = categoryRows.filter(row => !row.hidden).length;
      category.hidden = visibleRows === 0;
      if ((appliedType || appliedStatus || query) && visibleRows > 0) category.open = true;
    });
    resultCount.textContent = `${visibleCount} ${visibleCount === 1 ? 'bike' : 'bikes'}`;
    noResults.hidden = rows.length === 0 || visibleCount > 0;

    const activeCount = Number(Boolean(appliedType)) + Number(Boolean(appliedStatus));
    filterCount.hidden = activeCount === 0;
    filterCount.textContent = String(activeCount);
    filterButton.classList.toggle('active', activeCount > 0);
  }

  function renderQuickButtons() {
    typeLabel.textContent = draftType || 'Type';
    statusLabel.textContent = draftStatus || 'Status';
    typeButton.classList.toggle('active', Boolean(draftType) || openList === 'type');
    statusButton.classList.toggle('active', Boolean(draftStatus) || openList === 'status');
    typeButton.setAttribute('aria-expanded', String(openList === 'type'));
    statusButton.setAttribute('aria-expanded', String(openList === 'status'));
  }

  function renderOptions() {
    optionsList.replaceChildren();
    if (!openList) {
      optionsList.hidden = true;
      return;
    }

    const isType = openList === 'type';
    const optionValues = isType ? types : statuses;
    const selectedValue = isType ? draftType : draftStatus;
    const allLabel = isType ? 'All types' : 'All statuses';
    optionsList.hidden = false;

    [null, ...optionValues].forEach(value => {
      const option = document.createElement('button');
      option.type = 'button';
      option.className = `filter-option${value === selectedValue ? ' selected' : ''}`;
      option.setAttribute('role', 'option');
      option.setAttribute('aria-selected', String(value === selectedValue));
      const label = document.createElement('span');
      label.textContent = value || allLabel;
      const check = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      check.setAttribute('viewBox', '0 0 24 24');
      check.setAttribute('fill', 'none');
      check.setAttribute('stroke', 'currentColor');
      check.setAttribute('stroke-width', '3');
      const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      path.setAttribute('d', 'm5 13 4 4L19 7');
      check.append(path);
      option.append(label, check);
      option.addEventListener('click', () => {
        if (isType) draftType = value;
        else draftStatus = value;
        openList = null;
        renderQuickButtons();
        renderOptions();
      });
      optionsList.append(option);
    });
  }

  function openFilters() {
    draftType = appliedType;
    draftStatus = appliedStatus;
    openList = null;
    filterPopover.hidden = false;
    filterButton.setAttribute('aria-expanded', 'true');
    renderQuickButtons();
    renderOptions();
  }

  function closeFilters() {
    filterPopover.hidden = true;
    filterButton.setAttribute('aria-expanded', 'false');
    openList = null;
    renderQuickButtons();
    renderOptions();
  }

  filterButton.addEventListener('click', () => {
    if (filterPopover.hidden) openFilters();
    else closeFilters();
  });
  typeButton.addEventListener('click', () => {
    openList = openList === 'type' ? null : 'type';
    renderQuickButtons();
    renderOptions();
  });
  statusButton.addEventListener('click', () => {
    openList = openList === 'status' ? null : 'status';
    renderQuickButtons();
    renderOptions();
  });
  document.getElementById('inventoryClearFilters').addEventListener('click', () => {
    appliedType = draftType = null;
    appliedStatus = draftStatus = null;
    openList = null;
    renderQuickButtons();
    renderOptions();
    renderRows();
  });
  document.getElementById('inventoryApplyFilters').addEventListener('click', () => {
    appliedType = draftType;
    appliedStatus = draftStatus;
    closeFilters();
    renderRows();
  });
  searchInput.addEventListener('input', renderRows);
  document.addEventListener('click', event => {
    if (!filterPopover.hidden && !event.composedPath().includes(filterWrap)) closeFilters();
  });

  window.openInventory = function (bikeType = null) {
    appliedType = bikeType || null;
    appliedStatus = null;
    draftType = appliedType;
    draftStatus = null;
    openList = null;
    searchInput.value = '';
    closeFilters();
    renderRows();
    goTo('inventory');
  };

  renderRows();
})();
</script>