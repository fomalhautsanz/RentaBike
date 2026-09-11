{{-- INVENTORY SCREEN --}}
<section class="screen" id="inventory">
  <div class="page-header">
    <button class="back-btn" onclick="goTo('home')">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
    <h2>Bike Inventory</h2>

    {{-- Action buttons (right side, permission-gated) --}}
    <div style="display:flex;gap:6px;margin-left:auto;align-items:center">
      <a href="{{ route('staff.export') }}"
        style="display:inline-flex;align-items:center;gap:6px;padding:9px 13px;background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-md);font-size:13px;font-weight:600;color:var(--gray-700);text-decoration:none;box-shadow:var(--shadow-sm)">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="7 10 12 15 17 10"/>
          <line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Export
      </a>
      @if($canAddInventory ?? false)
        <button
          type="button"
          onclick="openBikeAction('add')"
          class="primary-btn"
          style="width:auto;margin-top:0;padding:9px 13px;font-size:13px;border-radius:var(--radius-md)">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Add Bike
        </button>
      @endif
    </div>
  </div>

  <div class="content">

    {{-- Permission notice: read-only mode --}}
    @if(!($canEditInventory ?? false) && !($canDeleteInventory ?? false) && !($canAddInventory ?? false))
      <div style="background:var(--blue-50);border:1px solid #bfdbfe;border-radius:var(--radius-md);padding:10px 14px;margin-bottom:16px;display:flex;align-items:center;gap:8px">
        <svg width="16" height="16" fill="none" stroke="#2563eb" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span style="font-size:12px;color:#1d4ed8;font-weight:500">You have view-only access to the inventory.</span>
      </div>
    @endif

    {{-- Dynamic bike categories from backend --}}
    @forelse($bikeCategories ?? [] as $category => $bikes)
    <div class="inv-category" style="margin-bottom:12px">
      <div class="inv-cat-header">
        <div class="inv-cat-icon" style="background:var(--green-50);font-size:18px">🚲</div>
        <h3>{{ $category }}</h3>
        <span class="cat-count">{{ count($bikes) }} {{ Str::plural('unit', count($bikes)) }}</span>
      </div>
      <div class="inv-list">
        @foreach($bikes as $bike)
        <div class="inv-row" style="gap:8px">
          {{-- Bike info --}}
          <div style="flex:1;min-width:0">
            <div class="inv-row-id">{{ $bike->qr_code }}</div>
            <div class="inv-row-name">{{ $bike->model }} · {{ $bike->make }}</div>
          </div>

          {{-- Condition badge (small) --}}
          <span style="font-size:10px;font-weight:600;color:var(--gray-500);background:var(--gray-100);padding:2px 7px;border-radius:999px;white-space:nowrap;flex-shrink:0">
            {{ ucfirst($bike->condition) }}
          </span>

          {{-- Status badge --}}
          <span class="badge {{ match($bike->status) {
            'available' => 'badge-green',
            'rented'    => 'badge-blue',
            default     => 'badge-orange',
          } }}" style="flex-shrink:0">
            {{ ucfirst($bike->status) }}
          </span>

          {{-- Action buttons (permission-gated, only if not rented) --}}
          @if(($canEditInventory ?? false) || ($canDeleteInventory ?? false))
            <div style="display:flex;gap:4px;flex-shrink:0">
              @if($canEditInventory ?? false)
                <button
                  class="action-btn"
                  type="button"
                  onclick="openBikeAction('edit', '{{ $bike->bike_id }}', '{{ addslashes($bike->model) }}')"
                  style="width:30px;height:30px"
                  title="Edit {{ $bike->qr_code }}">
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                </button>
              @endif
              @if(($canDeleteInventory ?? false) && $bike->status !== 'rented')
                <button
                  class="action-btn"
                  type="button"
                  onclick="openBikeAction('delete', '{{ $bike->bike_id }}', '{{ addslashes($bike->model) }}')"
                  style="width:30px;height:30px;color:var(--red-600)"
                  title="Delete {{ $bike->qr_code }}">
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
    {{-- Fallback: static placeholder data while backend isn't wired yet --}}
    <div class="inv-category" style="margin-bottom:12px">
      <div class="inv-cat-header">
        <div class="inv-cat-icon" style="background:#eff6ff">🚴</div>
        <h3>Road Bikes</h3>
        <span class="cat-count">3 units</span>
      </div>
      <div class="inv-list">
        @foreach([['RB-001','Available','badge-green'],['RB-002','Rented','badge-blue'],['RB-003','Repair','badge-orange']] as [$id,$status,$badge])
        <div class="inv-row">
          <div style="flex:1"><div class="inv-row-id">{{ $id }}</div><div class="inv-row-name">Road Bike</div></div>
          <span class="badge {{ $badge }}">{{ $status }}</span>
        </div>
        @endforeach
      </div>
    </div>

    <div class="inv-category" style="margin-bottom:12px">
      <div class="inv-cat-header">
        <div class="inv-cat-icon" style="background:#faf5ff">🛺</div>
        <h3>Sidecar Bikes</h3>
        <span class="cat-count">2 units</span>
      </div>
      <div class="inv-list">
        @foreach([['SC-001','Available','badge-green'],['SC-002','Available','badge-green']] as [$id,$status,$badge])
        <div class="inv-row">
          <div style="flex:1"><div class="inv-row-id">{{ $id }}</div><div class="inv-row-name">Sidecar Bike</div></div>
          <span class="badge {{ $badge }}">{{ $status }}</span>
        </div>
        @endforeach
      </div>
    </div>

    <div class="inv-category" style="margin-bottom:12px">
      <div class="inv-cat-header">
        <div class="inv-cat-icon" style="background:#f0fdf4">🚲</div>
        <h3>Children's Bikes</h3>
        <span class="cat-count">4 units</span>
      </div>
      <div class="inv-list">
        @foreach([['CB-001','Available','badge-green'],['CB-002','Rented','badge-blue'],['CB-003','Available','badge-green'],['CB-004','Repair','badge-orange']] as [$id,$status,$badge])
        <div class="inv-row">
          <div style="flex:1"><div class="inv-row-id">{{ $id }}</div><div class="inv-row-name">Kids Bike</div></div>
          <span class="badge {{ $badge }}">{{ $status }}</span>
        </div>
        @endforeach
      </div>
    </div>
    @endforelse

  </div>
</section>