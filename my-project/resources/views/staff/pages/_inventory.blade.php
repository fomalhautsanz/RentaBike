{{-- INVENTORY SCREEN --}}
<section class="screen" id="inventory">
  <div class="page-header inventory-flat-header">
    <button class="back-btn" onclick="goTo('home')" aria-label="Back to home">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
    <h2>Bike Inventory</h2>

    <div class="inventory-flat-actions">
      <a href="{{ route('staff.export') }}"
        class="inventory-flat-button"
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="7 10 12 15 17 10"/>
          <line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Export
      </a>
      @if($canAddInventory ?? false)
        <button type="button" class="inventory-flat-button inventory-flat-button-primary" onclick="goTo('inventory-create')">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Add Bike
        </button>
      @endif
    </div>
  </div>

  <div class="content inventory-flat-content">

    {{-- View-only notice --}}
    @if(!($canEditInventory ?? false) && !($canDeleteInventory ?? false) && !($canAddInventory ?? false))
      <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:var(--radius-md);padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;gap:8px">
        <svg width="15" height="15" fill="none" stroke="#2563eb" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span style="font-size:12px;color:#1d4ed8;font-weight:500">You have view-only access to this inventory.</span>
      </div>
    @endif

    <div class="inventory-flat-search-row">
      <label class="inventory-flat-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <input id="inventorySearch" type="search" placeholder="Search by ID or model..." autocomplete="off">
      </label>
      <div class="inventory-filter-wrap">
        <button type="button" class="inventory-filter-button" id="inventoryFilterButton" aria-expanded="false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
          Filter <span id="inventoryFilterCount" hidden>0</span>
        </button>
        <div class="inventory-filter-popover" id="inventoryFilterPopover" hidden>
          <label>Bike type
            <select id="inventoryTypeFilter">
              <option value="">All types</option>
              @foreach($bikeCategories->keys() as $bikeType)
                <option value="{{ $bikeType }}">{{ $bikeType }}</option>
              @endforeach
            </select>
          </label>
          <label>Status
            <select id="inventoryStatusFilter">
              <option value="">All status</option>
              <option value="available">Available</option>
              <option value="rented">Rented</option>
              <option value="repair">Maintenance</option>
            </select>
          </label>
          <div class="inventory-filter-actions">
            <button type="button" id="inventoryFilterClear">Clear</button>
            <button type="button" id="inventoryFilterApply">Apply</button>
          </div>
        </div>
      </div>
    </div>
    <p class="inventory-result-count" id="inventoryResultCount"></p>

    <div class="inventory-flat-list" id="inventoryFlatList">
      @forelse($bikes as $bike)
        @php
          $conditionLabel = strtolower((string) $bike->condition) === 'good' ? 'Good' : (strtolower((string) $bike->condition) === 'missing' ? 'Missing' : 'Fair');
          $statusLabel = $bike->status === 'repair' ? 'Maintenance' : ucfirst($bike->status);
        @endphp
        <div class="inventory-flat-row" data-bike-row data-type="{{ $bike->bike_type }}" data-status="{{ $bike->status }}" data-search="{{ strtolower($bike->qr_code . ' ' . $bike->model . ' ' . $bike->make . ' ' . $bike->bike_type) }}">
          <div class="inventory-flat-bike-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/></svg>
          </div>
          <div class="inventory-flat-description">
            <p class="inventory-flat-id">{{ $bike->qr_code }}</p>
            <p class="inventory-flat-meta"><strong>{{ $bike->bike_type }}</strong> · {{ $bike->model }} · {{ $bike->make }}</p>
          </div>
          <span class="inventory-pill condition">{{ $conditionLabel }}</span>
          <span class="inventory-pill {{ $bike->status === 'available' ? 'available' : ($bike->status === 'rented' ? 'rented' : 'maintenance') }}">{{ $statusLabel }}</span>
          @if($canEditInventory ?? false)
            <button type="button" class="inventory-icon-button edit" title="Edit {{ $bike->qr_code }}"
              onclick="event.stopPropagation(); openBikeAction('edit', { id: this.dataset.bikeId, qrCode: this.dataset.qrCode, model: this.dataset.model, make: this.dataset.make, type: this.dataset.type, condition: this.dataset.condition })"
              data-bike-id="{{ $bike->qr_code }}" data-qr-code="{{ $bike->qr_code }}" data-model="{{ $bike->model }}" data-make="{{ $bike->make }}" data-type="{{ $bike->bike_type }}" data-condition="{{ $conditionLabel === 'Good' ? 'Good' : ($conditionLabel === 'Missing' ? 'Missing' : 'Needs Repair') }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
            </button>
          @endif
          @if(($canDeleteInventory ?? false) && $bike->status !== 'rented')
            <button type="button" class="inventory-icon-button delete" title="Delete {{ $bike->qr_code }}"
              onclick="event.stopPropagation(); openBikeAction('delete', { id: this.dataset.bikeId })" data-bike-id="{{ $bike->qr_code }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2m2 0v14a1 1 0 01-1 1H7a1 1 0 01-1-1V6h12z"/></svg>
            </button>
          @endif
        </div>
      @empty
        <div class="inventory-flat-empty">No bikes have been added yet.</div>
      @endforelse
    </div>

  </div>
</section>

<script>
(() => {
  const search = document.getElementById('inventorySearch');
  const resultCount = document.getElementById('inventoryResultCount');
  const rows = [...document.querySelectorAll('[data-bike-row]')];
  const filterButton = document.getElementById('inventoryFilterButton');
  const filterPopover = document.getElementById('inventoryFilterPopover');
  const filterCount = document.getElementById('inventoryFilterCount');
  const typeFilter = document.getElementById('inventoryTypeFilter');
  const statusFilter = document.getElementById('inventoryStatusFilter');

  if (!search || !resultCount || !filterButton || !filterPopover) return;

  const render = () => {
    const query = search.value.trim().toLowerCase();
    const type = typeFilter.value;
    const status = statusFilter.value;
    let visible = 0;

    rows.forEach((row) => {
      const matches = (!query || row.dataset.search.includes(query))
        && (!type || row.dataset.type === type)
        && (!status || row.dataset.status === status);
      row.hidden = !matches;
      if (matches) visible += 1;
    });

    resultCount.textContent = `${visible} bike${visible === 1 ? '' : 's'}`;
    const active = Number(Boolean(type)) + Number(Boolean(status));
    filterCount.hidden = active === 0;
    filterCount.textContent = active;
    filterButton.classList.toggle('active', active > 0);
  };

  search.addEventListener('input', render);
  filterButton.addEventListener('click', (event) => {
    event.stopPropagation();
    const isOpen = !filterPopover.hidden;
    filterPopover.hidden = isOpen;
    filterButton.setAttribute('aria-expanded', String(!isOpen));
  });
  document.addEventListener('click', (event) => {
    if (!filterPopover.contains(event.target) && event.target !== filterButton) {
      filterPopover.hidden = true;
      filterButton.setAttribute('aria-expanded', 'false');
    }
  });
  document.getElementById('inventoryFilterClear').addEventListener('click', () => {
    typeFilter.value = '';
    statusFilter.value = '';
    render();
  });
  document.getElementById('inventoryFilterApply').addEventListener('click', () => {
    filterPopover.hidden = true;
    filterButton.setAttribute('aria-expanded', 'false');
    render();
  });

  render();
})();
</script>