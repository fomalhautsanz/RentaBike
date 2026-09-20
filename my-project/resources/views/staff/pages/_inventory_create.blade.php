{{-- ADD BIKE SCREEN --}}
<section class="screen" id="inventory-create">
  <div class="page-header">
    <button class="back-btn" type="button" onclick="goTo('inventory')" aria-label="Back to inventory">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
    <h2>Add Bike</h2>
  </div>

  <div class="content">
    <div class="form-card">

      @if($errors->any())
        <div style="background:var(--red-50,#fef2f2);border:1px solid #fecaca;border-radius:var(--radius-md);padding:11px 14px;margin-bottom:16px;display:flex;align-items:center;gap:8px">
          <svg width="15" height="15" fill="none" stroke="#dc2626" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <span style="font-size:12.5px;color:#dc2626;font-weight:500">{{ $errors->first() }}</span>
        </div>
      @endif

      <form method="POST" action="{{ route('staff.inventory.store') }}">
        @csrf

        {{-- QR Code --}}
        <div class="form-group">
          <label class="form-label" for="qr-code">Bike ID</label>
          <input id="qr-code" name="qr_code" class="form-input"
            value="{{ old('qr_code') }}" placeholder="e.g. RB-004" required autocomplete="off">
        </div>

        {{-- Model --}}
        <div class="form-group">
          <label class="form-label" for="bike-model">Model</label>
          <input id="bike-model" name="model" class="form-input"
            value="{{ old('model') }}" placeholder="e.g. City 300" required>
        </div>

        {{-- Make --}}
        <div class="form-group">
          <label class="form-label" for="bike-make">Make</label>
          <input id="bike-make" name="make" class="form-input"
            value="{{ old('make') }}" placeholder="e.g. Trek" required>
        </div>

        {{-- Bike Type --}}
        <div class="form-group">
          <label class="form-label" for="bike-type">Bike Type</label>
          <select id="bike-type" name="bike_type" class="form-select" required>
            @foreach(['Mountain Bike','City Bike',"Lady's/Men's Bike",'E-Scooter','Road Bike','Sidecar Bike',"Children's Bike"] as $type)
              <option value="{{ $type }}" @selected(old('bike_type','Mountain Bike') === $type)>{{ $type }}</option>
            @endforeach
          </select>
        </div>

        {{-- Condition --}}
        <div class="form-group">
          <label class="form-label" for="bike-condition">Condition</label>
          <select id="bike-condition" name="condition" class="form-select" required>
            @foreach(['Good','Needs Repair','Missing'] as $condition)
              <option value="{{ $condition }}" @selected(old('condition','Good') === $condition)>{{ $condition }}</option>
            @endforeach
          </select>
        </div>

        {{-- Submit --}}
        <button type="submit" class="primary-btn">
          <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="width:16px;height:16px">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Add Bike
        </button>

        <button type="button" class="primary-btn outline" style="margin-top:10px" onclick="goTo('inventory')">
          Cancel
        </button>

      </form>
    </div>
  </div>
</section>