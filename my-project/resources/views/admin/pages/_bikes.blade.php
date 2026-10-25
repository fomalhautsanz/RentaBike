<section id="page-bikes" class="page">
  <div class="page-header">
    <div>
      <div class="page-title">Bike Inventory</div>
      <div class="page-sub">Manage your bike fleet and QR code assignments</div>
    </div>
    <div style="display:flex;gap:10px">
      <button class="btn btn-outline">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="5" height="5" x="3" y="3" rx=".5"/><rect width="5" height="5" x="16" y="3" rx=".5"/><rect width="5" height="5" x="3" y="16" rx=".5"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/></svg>
        Generate QR Codes
      </button>
      <button class="btn btn-primary" onclick="openModal('add-bike-modal')">
        <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add New Bike
      </button>
    </div>
  </div>
  <div class="table-wrap">
    <div class="table-toolbar">
      <div class="search-wrap">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" class="search-input" placeholder="Search bikes by ID or name..." oninput="filterBikes(this.value)">
      </div>
      <select class="filter-select" onchange="filterBikeType(this.value)">
        <option value="">All Types</option>
        <option>E-Scooter</option><option>Lady's/Men's Bike</option><option>Mountain Bike</option><option>City Bike</option><option>Kiddie Bikes</option>
      </select>
      <select class="filter-select" onchange="filterBikeStatus(this.value)">
        <option value="">All Status</option>
        <option>Available</option><option>Rented</option><option>Maintenance</option>
      </select>
    </div>
    <table>
      <thead>
        <tr><th>Bike ID</th><th>Bike Name</th><th>Type</th><th>QR Code</th><th>Status</th><th>Condition</th><th>Last Maintenance</th><th>Actions</th></tr>
      </thead>
      <tbody id="bikes-tbody">
        @foreach($bikes ?? [] as $bike)
        <tr data-id="{{ $bike->bike_code }}" data-name="{{ $bike->name }}" data-type="{{ $bike->type }}" data-status="{{ $bike->status }}">
          <td style="font-weight:600">{{ $bike->bike_code }}</td>
          <td>{{ $bike->name }}</td>
          <td><span class="badge badge-gray">{{ $bike->type }}</span></td>
          <td>
            <button class="btn btn-outline btn-sm" onclick="openQR('{{ $bike->bike_code }}','{{ $bike->name }}','{{ $bike->qr_code }}')">
              <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="5" height="5" x="3" y="3" rx=".5"/><rect width="5" height="5" x="16" y="3" rx=".5"/><rect width="5" height="5" x="3" y="16" rx=".5"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/></svg>
              {{ $bike->qr_code }}
            </button>
          </td>
          <td>
            <span class="badge {{ $bike->status === 'Available' ? 'badge-green' : ($bike->status === 'Rented' ? 'badge-blue' : 'badge-orange') }}">
              {{ $bike->status }}
            </span>
          </td>
          <td>
            <span class="badge {{ $bike->condition === 'Good' ? 'badge-green' : ($bike->condition === 'Needs Repair' ? 'badge-yellow' : 'badge-red') }}">
              {{ $bike->condition }}
            </span>
          </td>
          <td style="color:#6b7280;font-size:13px">{{ $bike->last_maintenance ? $bike->last_maintenance->format('Y-m-d') : 'N/A' }}</td>
          <td>
            <button class="action-btn" onclick="openEditBike('{{ $bike->bike_code }}','{{ $bike->name }}','{{ $bike->type }}','{{ $bike->status }}','{{ $bike->condition }}')">
              <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            <button class="action-btn" onclick="openDeleteBike('{{ $bike->bike_code }}','{{ $bike->name }}')">
              <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </button>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div class="table-footer">
      <p>Showing {{ count($bikes ?? []) }} bikes</p>
      <div class="pagination"></div>
    </div>
  </div>
</section>