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
            $condition = strtolower($bike->condition ?? 'good');
            $conditionLabel = match ($condition) {
              'missing' => 'Missing',
              'repair' => 'Needs Repair',
              default => 'Good',
            };
            $displayStatus = $condition === 'good' ? ucfirst(strtolower($bike->status)) : 'Repair';
            $statusClass = match ($displayStatus) {
              'Available' => 'badge-green',
              'Rented' => 'badge-blue',
              default => 'badge-orange',
            };
            $isReportable = $condition !== 'good';
            $modalType = strtolower($bike->status) === 'rented' ? 'rented' : 'available';
          @endphp
          @if($isReportable)
          <div class="inv-row" data-bike-code="{{ $bike->qr_code }}" data-id="{{ $bike->qr_code }}" data-issue="{{ $conditionLabel }}" data-date="{{ optional($bike->created_at)->format('M d, Y') }}" data-report-type="{{ $condition === 'missing' ? 'missing' : 'damage' }}" onclick="openModal('maintenance', { id: this.dataset.id, issue: this.dataset.issue, date: this.dataset.date, reportType: this.dataset.reportType })" style="cursor:pointer">
          @else
          <div class="inv-row" data-bike-code="{{ $bike->qr_code }}" data-status="{{ $bike->status }}" onclick="openModal('{{ $modalType }}', { id: '{{ $bike->qr_code }}' })" style="cursor:pointer">
          @endif
            <div>
              <div class="inv-row-id">{{ $bike->qr_code }}</div>
              <div class="inv-row-name">{{ $bike->bike_type }} · {{ $conditionLabel }}</div>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
              <span class="badge {{ $statusClass }}" data-bike-status>{{ $displayStatus }}</span>
              @if($isReportable)
                <span style="color:var(--gray-400);font-size:16px" aria-label="Report issue">›</span>
              @endif
              <button type="button" class="back-btn" title="Edit bike" aria-label="Edit {{ $bike->qr_code }}" data-bike-id="{{ $bike->qr_code }}" data-bike-type="{{ $bike->bike_type }}" data-bike-condition="{{ $conditionLabel }}" onclick="event.preventDefault(); event.stopPropagation(); openBikeAction('edit', { id: this.dataset.bikeId, type: this.dataset.bikeType, condition: this.dataset.bikeCondition })">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
              </button>
              <button type="button" class="back-btn" title="Delete bike" aria-label="Delete {{ $bike->qr_code }}" data-bike-id="{{ $bike->qr_code }}" onclick="event.preventDefault(); event.stopPropagation(); openBikeAction('delete', { id: this.dataset.bikeId })">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6v14H5V6"/><path d="M10 11v5M14 11v5"/></svg>
              </button>
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
