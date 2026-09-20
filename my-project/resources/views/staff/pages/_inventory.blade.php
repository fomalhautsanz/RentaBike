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

    {{-- Live categories from the bicycle table --}}
    @forelse($bikeCategories as $category => $bikes)
    <div class="inv-category" style="margin-bottom:12px">
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
          $statusBadge = match($bike->status) {
            'available' => ['class' => 'badge-green', 'label' => 'Available'],
            'rented'    => ['class' => 'badge-blue',  'label' => 'Rented'],
            'repair'    => ['class' => 'badge-orange','label' => 'Repair'],
            default     => ['class' => 'badge-gray',  'label' => ucfirst($bike->status)],
          };
        @endphp
        <div class="inv-row" style="gap:8px;align-items:center">
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
                <button type="button"
                  onclick="event.stopPropagation(); openBikeAction('edit', { id: this.dataset.bikeId, qrCode: this.dataset.qrCode, model: this.dataset.model, make: this.dataset.make, type: this.dataset.type, condition: this.dataset.condition })"
                  data-bike-id="{{ $bike->qr_code }}"
                  data-qr-code="{{ $bike->qr_code }}"
                  data-model="{{ $bike->model }}"
                  data-make="{{ $bike->make }}"
                  data-type="{{ $bike->bike_type }}"
                  data-condition="{{ strtolower($bike->condition) === 'good' ? 'Good' : (strtolower($bike->condition) === 'missing' ? 'Missing' : 'Needs Repair') }}"
                  class="action-btn" style="width:30px;height:30px" title="Edit {{ $bike->qr_code }}">
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                </button>
              @endif
              @if(($canDeleteInventory ?? false) && $bike->status !== 'rented')
                <button type="button"
                  onclick="event.stopPropagation(); openBikeAction('delete', { id: this.dataset.bikeId })"
                  data-bike-id="{{ $bike->qr_code }}"
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
      <div class="empty-state">No bikes have been added yet.</div>
    @endforelse

  </div>
</section>