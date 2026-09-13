{{-- ADD BIKE SCREEN --}}
<section class="screen" id="inventory-create">
  <div class="page-header">
    <button class="back-btn" onclick="goTo('inventory')" aria-label="Back to inventory">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <h2>Add bike</h2>
  </div>

  <div class="form-wrap">
    <div class="form-card">
      @if($errors->any())
        <div style="background:var(--red-50);color:var(--red-600);border-radius:var(--radius-md);padding:12px 14px;margin-bottom:16px;font-size:12px">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('staff.inventory.store') }}">
        @csrf
        <div class="form-group">
          <label class="form-label" for="bike-type">Type</label>
          <select id="bike-type" name="type" class="form-select" required>
            @foreach(['Mountain Bike', 'City Bike', "Lady's/Men's Bike", 'E-Scooter', 'Kiddie Bikes'] as $type)
              <option value="{{ $type }}" @selected(old('type') === $type)>{{ $type }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="bike-condition">Condition</label>
          <select id="bike-condition" name="condition" class="form-select" required>
            @foreach(['Good', 'Needs Repair', 'Missing'] as $condition)
              <option value="{{ $condition }}" @selected(old('condition', 'Good') === $condition)>{{ $condition }}</option>
            @endforeach
          </select>
        </div>
        <button type="submit" class="primary-btn">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add to inventory
        </button>
      </form>
    </div>
  </div>
</section>
