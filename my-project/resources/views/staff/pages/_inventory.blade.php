{{-- INVENTORY SCREEN --}}
<section class="screen" id="inventory">
  <div class="page-header">
    <button class="back-btn" onclick="goTo('home')">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
    <h2>Inventory</h2>
  </div>

  <div class="content">
    <div class="inv-category">
      <div class="inv-cat-header">
        <div class="inv-cat-icon" style="background:#eff6ff">🚴</div>
        <h3>Road Bikes</h3>
        <span class="cat-count">3 units</span>
      </div>
      <div class="inv-list">
        <div class="inv-row">
          <div><div class="inv-row-id">RB-001</div><div class="inv-row-name">Road Bike</div></div>
          <span class="badge badge-green">Available</span>
        </div>
        <div class="inv-row">
          <div><div class="inv-row-id">RB-002</div><div class="inv-row-name">Road Bike</div></div>
          <span class="badge badge-blue">Rented</span>
        </div>
        <div class="inv-row">
          <div><div class="inv-row-id">RB-003</div><div class="inv-row-name">Road Bike</div></div>
          <span class="badge badge-orange">Repair</span>
        </div>
      </div>
    </div>

    <div class="inv-category">
      <div class="inv-cat-header">
        <div class="inv-cat-icon" style="background:#faf5ff">🛺</div>
        <h3>Sidecar Bikes</h3>
        <span class="cat-count">2 units</span>
      </div>
      <div class="inv-list">
        <div class="inv-row">
          <div><div class="inv-row-id">SC-001</div><div class="inv-row-name">Sidecar Bike</div></div>
          <span class="badge badge-green">Available</span>
        </div>
        <div class="inv-row">
          <div><div class="inv-row-id">SC-002</div><div class="inv-row-name">Sidecar Bike</div></div>
          <span class="badge badge-green">Available</span>
        </div>
      </div>
    </div>

    <div class="inv-category">
      <div class="inv-cat-header">
        <div class="inv-cat-icon" style="background:#f0fdf4">🚲</div>
        <h3>Children's Bikes</h3>
        <span class="cat-count">4 units</span>
      </div>
      <div class="inv-list">
        <div class="inv-row">
          <div><div class="inv-row-id">CB-001</div><div class="inv-row-name">Kids Bike</div></div>
          <span class="badge badge-green">Available</span>
        </div>
        <div class="inv-row">
          <div><div class="inv-row-id">CB-002</div><div class="inv-row-name">Kids Bike</div></div>
          <span class="badge badge-blue">Rented</span>
        </div>
        <div class="inv-row">
          <div><div class="inv-row-id">CB-003</div><div class="inv-row-name">Kids Bike</div></div>
          <span class="badge badge-green">Available</span>
        </div>
        <div class="inv-row">
          <div><div class="inv-row-id">CB-004</div><div class="inv-row-name">Kids Bike</div></div>
          <span class="badge badge-orange">Repair</span>
        </div>
      </div>
    </div>
  </div>
</section>