<section id="page-staff" class="page">
  <div class="page-header">
    <div>
      <div class="page-title">Staff Management</div>
      <div class="page-sub">Manage your team members and their access</div>
    </div>
    <button class="btn btn-primary" onclick="openModal('add-staff-modal')">
      <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Staff Member
    </button>
  </div>
  <div class="table-wrap">
    <div class="table-toolbar">
      <div class="search-wrap">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" class="search-input" placeholder="Search staff members..." oninput="filterStaff(this.value)">
      </div>
      <select class="filter-select" onchange="filterStaffRole(this.value)">
        <option value="">All Roles</option>
        <option>Manager</option><option>Staff</option><option>Technician</option>
      </select>
      <select class="filter-select" onchange="filterStaffStatus(this.value)">
        <option value="">All Status</option>
        <option>Active</option><option>On Leave</option>
      </select>
    </div>
    <table>
      <thead>
        <tr><th>Staff Member</th><th>Contact</th><th>Role</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody id="staff-tbody">
        @foreach($staff ?? [] as $member)
        <tr data-name="{{ $member->name }}" data-role="{{ $member->role }}" data-status="{{ $member->status }}">
          <td>
            <div style="display:flex;align-items:center;gap:12px">
              <div class="avatar" style="flex-shrink:0">{{ strtoupper(substr($member->name, 0, 2)) }}</div>
              <div>
                <div style="font-weight:500;color:#111827">{{ $member->name }}</div>
                <div style="font-size:12px;color:#9ca3af">ID: #{{ str_pad($member->id, 4, '0', STR_PAD_LEFT) }}</div>
              </div>
            </div>
          </td>
          <td>
            <div class="contact-cell">
              <div class="contact-line">
                <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                {{ $member->email }}
              </div>
              <div class="contact-line">
                <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.07 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 2.18 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L6.91 9.91a16 16 0 0 0 6.16 6.16l1.27-.52a2 2 0 0 1 2.11.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                {{ $member->phone ?? 'N/A' }}
              </div>
            </div>
          </td>
          <td>
            <span class="badge {{ $member->role === 'Manager' ? 'badge-purple' : ($member->role === 'Technician' ? 'badge-blue' : 'badge-gray') }}">
              {{ $member->role }}
            </span>
          </td>
          <td>
            <span class="badge {{ $member->status === 'Active' ? 'badge-green' : 'badge-yellow' }}">
              {{ $member->status }}
            </span>
          </td>
          <td>
            <button class="action-btn" onclick="openEditStaff('{{ $member->name }}','{{ $member->role }}','{{ $member->status }}')">
              <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            <button class="action-btn" onclick="openDeleteStaff('{{ $member->name }}')">
              <svg class="icon-sm" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </button>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div class="table-footer">
      <p>Showing {{ count($staff ?? []) }} staff members</p>
      <div class="pagination"></div>
    </div>
  </div>
</section>