{{-- INVENTORY SCREEN --}}
<section class="screen" id="inventory">
  <div class="page-header">
    <button class="back-btn" onclick="goTo('home')" aria-label="Back to home">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <h2>Inventory</h2>
    <button class="back-btn" onclick="goTo('inventory-create')" aria-label="Add bike" style="margin-left:auto">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    </button>
  </div>

  <div class="content">
    @if(session('status'))
      <div id="inventory-status" data-message="{{ session('status') }}" hidden></div>
    @endif

    <div class="section-title">
      <h3>All bikes</h3>
      <span class="cat-count">{{ count($bikes ?? []) }} {{ count($bikes ?? []) === 1 ? 'unit' : 'units' }}</span>
    </div>

    <div class="inv-category">
      <div class="inv-list">
        @forelse($bikes ?? [] as $bike)
          @php
            $displayStatus = $bike->condition === 'Good' ? $bike->status : 'Repair';
            $statusClass = match ($displayStatus) {
              'Available' => 'badge-green',
              'Rented' => 'badge-blue',
              default => 'badge-orange',
            };
            $isReportable = $bike->condition !== 'Good';
            $modalType = $bike->status === 'Rented' ? 'rented' : 'available';
          @endphp
          @if($isReportable)
          <div class="inv-row" data-bike-code="{{ $bike->bike_code }}" data-id="{{ $bike->bike_code }}" data-issue="{{ $bike->condition }}" data-date="{{ optional($bike->created_at)->format('M d, Y') }}" data-report-type="{{ $bike->condition === 'Missing' ? 'missing' : 'damage' }}" onclick="openModal('maintenance', { id: this.dataset.id, issue: this.dataset.issue, date: this.dataset.date, reportType: this.dataset.reportType })" style="cursor:pointer">
          @else
          <div class="inv-row" data-bike-code="{{ $bike->bike_code }}" data-status="{{ $bike->status }}" onclick="openModal('{{ $modalType }}', { id: '{{ $bike->bike_code }}' })" style="cursor:pointer">
          @endif
            <div>
              <div class="inv-row-id">{{ $bike->bike_code }}</div>
              <div class="inv-row-name">{{ $bike->name }} · {{ $bike->type }} · {{ $bike->condition }}</div>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
              <span class="badge {{ $statusClass }}" data-bike-status>{{ $displayStatus }}</span>
              @if($isReportable)
                <span style="color:var(--gray-400);font-size:16px" aria-label="Report issue">›</span>
              @endif
            </div>
          </div>
        @empty
          <div style="padding:28px 16px;text-align:center;color:var(--gray-500);font-size:13px">
            No bikes have been added yet.
          </div>
        @endforelse
      </div>
    </div>
  </div>
</section>
