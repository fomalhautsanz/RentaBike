{{-- INVENTORY SCREEN --}}
<section class="screen" id="inventory">
  <div class="page-header">
    <button class="back-btn" onclick="goTo('home')" aria-label="Back to home">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
    <h2>Bike Inventory</h2>
    <div style="display:flex;gap:6px;margin-left:auto;align-items:center">
      <a href="{{ route('staff.export') }}"
        style="display:inline-flex;align-items:center;gap:5px;padding:8px 12px;background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-md);font-size:12.5px;font-weight:600;color:var(--gray-700);text-decoration:none;box-shadow:var(--shadow-sm)">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="7 10 12 15 17 10"/>
          <line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Export
      </a>
      @if($canAddInventory ?? false)
        <button type="button" onclick="goTo('inventory-create')"
          style="display:inline-flex;align-items:center;gap:5px;padding:8px 12px;background:var(--green-600);border:none;border-radius:var(--radius-md);font-size:12.5px;font-weight:600;color:#fff;cursor:pointer;box-shadow:0 2px 8px rgba(22,163,74,.25)">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Add Bike
        </button>
      @endif
    </div>
  </div>

  <div class="content">

    {{-- Search and Filter --}}
    <div style="display:flex;gap:8px;margin-bottom:16px;position:relative">
      <div style="flex:1;position:relative">
        <svg width="14" height="14" fill="none" stroke="var(--gray-400)" stroke-width="2" viewBox="0 0 24 24"
          style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input
          type="text"
          id="inv-search"
          placeholder="Search by ID or model..."
          oninput="filterInventory()"
          style="width:100%;padding:9px 12px 9px 32px;border:1px solid var(--gray-200);border-radius:var(--radius-md);font-size:13px;background:var(--white);outline:none;color:var(--gray-900)">
      </div>
      <button type="button" id="inv-filter-toggle" onclick="toggleInventoryFilters()"
        style="display:inline-flex;align-items:center;gap:7px;padding:9px 14px;border:1px solid var(--gray-200);border-radius:var(--radius-md);font-size:13px;font-weight:600;background:var(--white);color:var(--gray-700);cursor:pointer;white-space:nowrap">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M4 6h16M7 12h10M10 18h4"/>
        </svg>
        Filter
      </button>
      <div id="inv-filter-panel" style="display:none;position:absolute;z-index:10;right:0;top:calc(100% + 8px);width:286px;padding:16px;background:var(--white);border:1px solid var(--gray-200);border-radius:16px;box-shadow:0 16px 32px rgba(15,23,42,.14)">
        <label for="inv-filter-type" style="display:block;margin-bottom:7px;font-size:13px;font-weight:600;color:var(--gray-700)">Bike type</label>
        <select id="inv-filter-type" onchange="filterInventory()"
          style="width:100%;padding:10px 12px;border:1px solid var(--gray-200);border-radius:10px;font-size:13px;background:var(--white);color:var(--gray-700);outline:none">
          <option value="">All types</option>
          <option value="Mountain Bike">Mountain Bike</option>
          <option value="City Bike">City Bike</option>
          <option value="Lady's/Men's Bike">Lady's/Men's Bike</option>
          <option value="E-Scooter">E-Scooter</option>
          <option value="Road Bike">Road Bike</option>
          <option value="Sidecar Bike">Sidecar Bike</option>
          <option value="Children's Bike">Children's Bike</option>
        </select>
        <label for="inv-filter-status" style="display:block;margin:14px 0 7px;font-size:13px;font-weight:600;color:var(--gray-700)">Status</label>
        <select id="inv-filter-status" onchange="filterInventory()"
          style="width:100%;padding:10px 12px;border:1px solid var(--gray-200);border-radius:10px;font-size:13px;background:var(--white);color:var(--gray-700);outline:none">
          <option value="">All status</option>
          <option value="available">Available</option>
          <option value="rented">Rented</option>
          <option value="repair">Maintenance</option>
        </select>
        <div style="display:flex;gap:8px;margin-top:14px">
          <button type="button" onclick="clearInventoryFilters()"
            style="flex:1;padding:10px 12px;border:1px solid var(--gray-200);border-radius:10px;font-size:13px;font-weight:600;background:var(--white);color:var(--gray-700);cursor:pointer">Clear</button>
          <button type="button" onclick="applyInventoryFilters()"
            style="flex:1;padding:10px 12px;border:none;border-radius:10px;font-size:13px;font-weight:600;background:var(--green-600);color:#fff;cursor:pointer">Apply</button>
        </div>
      </div>
    </div>

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

    {{-- Emoji and color mapping (restored) --}}
    @php
      $bikeEmojis = [
        'Mountain Bike'     => '🚵',
        'City Bike'         => '🚲',
        "Lady's/Men's Bike" => '🚲',
        'E-Scooter'         => '🛴',
        'Road Bike'         => '🚴',
        'Sidecar Bike'      => '🛺',
        "Children's Bike"   => '🚲',
      ];

      $categoryColors = [
        'Mountain Bike'     => '#f0fdf4',
        'City Bike'         => '#eff6ff',
        "Lady's/Men's Bike" => '#faf5ff',
        'E-Scooter'         => '#fff7ed',
        'Road Bike'         => '#f0fdf4',
        'Sidecar Bike'      => '#fefce8',
        "Children's Bike"   => '#fdf4ff',
      ];
    @endphp

    {{-- Live categories from backend --}}
    @forelse($bikeCategories ?? [] as $category => $bikes)
    <div class="inv-category inv-category-block" data-category="{{ $category }}" style="margin-bottom:12px">
      <div class="inv-cat-header">
        <div class="inv-cat-icon" style="background:{{ $categoryColors[$category] ?? '#f9fafb' }};font-size:20px">
          {{ $bikeEmojis[$category] ?? '🚲' }}
        </div>
        <h3>{{ $category }}</h3>
        <span class="cat-count">{{ count($bikes) }} {{ Str::plural('unit', count($bikes)) }}</span>
      </div>
      <div class="inv-list">
        @foreach($bikes as $bike)
        @php
          $statusVal   = strtolower($bike->status);
          $statusBadge = match($statusVal) {
            'available' => ['class' => 'badge-green',  'label' => 'Available'],
            'rented'    => ['class' => 'badge-blue',   'label' => 'Rented'],
            'repair'    => ['class' => 'badge-orange', 'label' => 'Repair'],
            default     => ['class' => 'badge-gray',   'label' => ucfirst($bike->status)],
          };
        @endphp
        <div class="inv-row inv-bike-row"
          data-qr="{{ strtolower($bike->qr_code) }}"
          data-model="{{ strtolower($bike->model) }}"
          data-category="{{ $category }}"
          data-status="{{ $statusVal }}"
          style="gap:8px;align-items:center">
          <div style="flex:1;min-width:0">
            <div class="inv-row-id">{{ $bike->qr_code }}</div>
            <div class="inv-row-name">{{ $bike->model }} · {{ $bike->make }}</div>
          </div>
          <span class="badge {{ $statusBadge['class'] }}" style="flex-shrink:0;font-size:11px">
            {{ $statusBadge['label'] }}
          </span>
          @if(($canEditInventory ?? false) || ($canDeleteInventory ?? false))
            <div style="display:flex;gap:4px;flex-shrink:0">
              @if($canEditInventory ?? false)
                <button type="button" onclick="goTo('inventory-edit')"
                  class="action-btn" style="width:30px;height:30px" title="Edit {{ $bike->qr_code }}">
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                </button>
              @endif
              @if(($canDeleteInventory ?? false) && $statusVal !== 'rented')
                <button type="button"
                  onclick="confirmDeleteBike('{{ $bike->bike_id }}','{{ addslashes($bike->qr_code) }}')"
                  class="action-btn" style="width:30px;height:30px;color:#ef4444" title="Delete {{ $bike->qr_code }}">
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    <path d="M10 11v6M14 11v6"/>
                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                  </svg>
                </button>
              @endif
            </div>
          @endif
        </div>
        @endforeach
      </div>
    </div>

    @empty

    {{-- Static placeholder --}}
    @foreach([
      ['Mountain Bike',   '🚵', '#f0fdf4', [['MB-001','Mountain Pro','Trek','available'],['MB-002','Mountain Pro','Trek','rented']]],
      ['City Bike',       '🚲', '#eff6ff', [['CB-001','City Cruiser','Giant','available'],['CB-002','City Cruiser','Giant','repair']]],
      ['E-Scooter',       '🛴', '#fff7ed', [['ES-001','E-Scooter X1','Segway','available']]],
      ['Road Bike',       '🚴', '#f0fdf4', [['RB-001','Road Racer','Specialized','available'],['RB-002','Road Racer','Specialized','rented']]],
      ["Children's Bike", '🚲', '#fdf4ff', [['KD-001','Kids 16"','Trek','available'],['KD-002','Kids 16"','Trek','rented']]],
    ] as [$cat, $emoji, $bg, $rows])
    <div class="inv-category inv-category-block" data-category="{{ $cat }}" style="margin-bottom:12px">
      <div class="inv-cat-header">
        <div class="inv-cat-icon" style="background:{{ $bg }};font-size:20px">{{ $emoji }}</div>
        <h3>{{ $cat }}</h3>
        <span class="cat-count">{{ count($rows) }} {{ count($rows) === 1 ? 'unit' : 'units' }}</span>
      </div>
      <div class="inv-list">
        @foreach($rows as [$id, $model, $make, $status])
        @php $cls = match($status) { 'available'=>'badge-green','rented'=>'badge-blue',default=>'badge-orange' }; @endphp
        <div class="inv-row inv-bike-row"
          data-qr="{{ strtolower($id) }}"
          data-model="{{ strtolower($model) }}"
          data-category="{{ $cat }}"
          data-status="{{ $status }}"
          style="gap:8px;align-items:center">
          <div style="flex:1;min-width:0">
            <div class="inv-row-id">{{ $id }}</div>
            <div class="inv-row-name">{{ $model }} · {{ $make }}</div>
          </div>
          <span class="badge {{ $cls }}" style="flex-shrink:0;font-size:11px">{{ ucfirst($status) }}</span>
        </div>
        @endforeach
      </div>
    </div>
    @endforeach

    @endforelse

    {{-- No results (shown by filterInventory() when search finds nothing) --}}
    <div id="inv-no-results" style="display:none;text-align:center;padding:32px 16px;color:var(--gray-400);font-size:13px">
      No bikes match your search.
    </div>

  </div>
</section>

<script>
function filterInventory() {
  const q      = (document.getElementById('inv-search')?.value ?? '').toLowerCase().trim();
  const type   = document.getElementById('inv-filter-type')?.value ?? '';
  const status = (document.getElementById('inv-filter-status')?.value ?? '').toLowerCase();
  let anyVisible = false;

  document.querySelectorAll('.inv-category-block').forEach(cat => {
    let catHasVisible = false;
    cat.querySelectorAll('.inv-bike-row').forEach(row => {
      const matchQ = !q || row.dataset.qr.includes(q) || row.dataset.model.includes(q);
      const matchT = !type || row.dataset.category === type;
      const matchS = !status || row.dataset.status === status;
      row.style.display = (matchQ && matchT && matchS) ? '' : 'none';
      if (matchQ && matchT && matchS) catHasVisible = true;
    });
    cat.style.display = catHasVisible ? '' : 'none';
    if (catHasVisible) anyVisible = true;
  });

  document.getElementById('inv-no-results').style.display = anyVisible ? 'none' : 'block';
}

function toggleInventoryFilters() {
  const panel = document.getElementById('inv-filter-panel');
  panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function clearInventoryFilters() {
  document.getElementById('inv-filter-type').value = '';
  document.getElementById('inv-filter-status').value = '';
  filterInventory();
}

function applyInventoryFilters() {
  filterInventory();
  document.getElementById('inv-filter-panel').style.display = 'none';
}

document.addEventListener('click', event => {
  const panel = document.getElementById('inv-filter-panel');
  const toggle = document.getElementById('inv-filter-toggle');

  if (panel?.style.display === 'block' && !panel.contains(event.target) && !toggle.contains(event.target)) {
    panel.style.display = 'none';
  }
});
</script>