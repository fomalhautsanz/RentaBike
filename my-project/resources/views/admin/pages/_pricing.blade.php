<section id="page-pricing" class="page {{ request('tab', session('active_tab')) === 'pricing' ? 'active' : '' }}">
  <div class="page-header">
    <div>
      <div class="page-title">Rental Pricing</div>
      <div class="page-sub">Set rental rates and package prices</div>
    </div>
  </div>

  @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#b91c1c">
      {{ $errors->first() }}
    </div>
  @endif

  <form method="POST" action="{{ route('admin.pricing.update') }}">
    @csrf
    <div class="table-wrap" style="padding:24px;margin-bottom:20px">
      <h2 style="font-size:17px;margin-bottom:6px">Hourly rental rates</h2>
      <p class="page-sub" style="margin:0 0 18px">These rates apply to new rentals. An active rental keeps the rate from its booked package for overdue calculations.</p>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px">
        @foreach(\App\Support\RentalPricing::MANAGED_HOURLY_TYPES as $key => $bikeType)
          <label class="form-group" style="margin:0">
            <span class="form-label">{{ $bikeType }} <span style="font-weight:400;color:#6b7280">/ hour</span></span>
            <span style="display:flex;align-items:center;gap:8px">
              <span style="color:#6b7280">₱</span>
              <input class="form-input" type="number" name="hourly_rates[{{ $key }}]"
                     min="0" max="100000" step="0.01" required
                     value="{{ old("hourly_rates.{$key}", $pricingSettings['hourly_rates'][$key]) }}">
            </span>
          </label>
        @endforeach
      </div>
    </div>

    <div class="table-wrap" style="padding:24px;margin-bottom:20px">
      <h2 style="font-size:17px;margin-bottom:6px">E-Scooter packages</h2>
      <p class="page-sub" style="margin:0 0 18px">The 60-minute package price is also the overdue charge per started hour.</p>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
        @foreach(\App\Support\RentalPricing::SCOOTER_PACKAGE_MINUTES as $minutes)
          <label class="form-group" style="margin:0">
            <span class="form-label">{{ $minutes }} minutes</span>
            <span style="display:flex;align-items:center;gap:8px">
              <span style="color:#6b7280">₱</span>
              <input class="form-input" type="number" name="scooter_packages[{{ $minutes }}]"
                     min="0" max="100000" step="0.01" required
                     value="{{ old("scooter_packages.{$minutes}", $pricingSettings['scooter_packages'][$minutes]) }}">
            </span>
          </label>
        @endforeach
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end">
      <button class="btn btn-primary" type="submit">Save Pricing</button>
    </div>
  </form>
</section>
