{{-- STAFF MANAGEMENT SCREEN --}}
<section class="screen" id="staff-management">
  <div class="page-header">
    <button class="back-btn" onclick="goTo('home')">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
    <h2>Staff Management</h2>
    <button
      class="primary-btn"
      type="button"
      onclick="openStaffAction('add')"
      style="margin-left:auto;width:auto;padding:10px 16px;margin-top:0;font-size:13px;border-radius:var(--radius-md)">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Add Staff
    </button>
  </div>

  <div class="content">

    {{-- Summary bar --}}
    <div style="display:flex;gap:8px;margin-bottom:16px">
      <div style="flex:1;background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-md);padding:12px 14px;box-shadow:var(--shadow-sm)">
        <div style="font-size:20px;font-weight:700;font-family:'Space Mono',monospace;color:var(--gray-900)">
          {{ count($staffMembers ?? []) }}
        </div>
        <div style="font-size:11px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;margin-top:2px">
          Total Staff
        </div>
      </div>
      <div style="flex:1;background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-md);padding:12px 14px;box-shadow:var(--shadow-sm)">
        <div style="font-size:20px;font-weight:700;font-family:'Space Mono',monospace;color:var(--green-700)">
          {{ collect($staffMembers ?? [])->where('status', 'active')->count() }}
        </div>
        <div style="font-size:11px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;margin-top:2px">
          Active
        </div>
      </div>
      <div style="flex:1;background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-md);padding:12px 14px;box-shadow:var(--shadow-sm)">
        <div style="font-size:20px;font-weight:700;font-family:'Space Mono',monospace;color:var(--yellow-600)">
          {{ collect($staffMembers ?? [])->where('status', 'inactive')->count() }}
        </div>
        <div style="font-size:11px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;margin-top:2px">
          Inactive
        </div>
      </div>
    </div>

    {{-- Staff list --}}
    <div class="section-title">
      <h3>Staff Accounts</h3>
    </div>

    @forelse($staffMembers ?? [] as $member)
    <div class="staff-card" style="background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-lg);padding:14px 16px;margin-bottom:10px;box-shadow:var(--shadow-sm)">

      {{-- Top row: avatar + name + badges --}}
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
        <div style="width:42px;height:42px;border-radius:50%;background:var(--green-50);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:var(--green-700);flex-shrink:0">
          {{ strtoupper(substr($member->full_name, 0, 2)) }}
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:14px;font-weight:700;color:var(--gray-900);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            {{ $member->full_name }}
          </div>
          <div style="font-size:12px;color:var(--gray-500);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            {{ $member->email }}
          </div>
        </div>
        {{-- Status badge --}}
        <span class="badge {{ $member->status === 'active' ? 'badge-green' : 'badge-yellow' }}" style="flex-shrink:0">
          {{ ucfirst($member->status) }}
        </span>
      </div>

      {{-- Bottom row: role + permissions count + actions --}}
      <div style="display:flex;align-items:center;gap:8px">
        <span class="badge badge-gray" style="font-size:11px">
          {{ $member->role }}
        </span>

        @php
          $permCount = count($member->permissions ?? []);
        @endphp
        @if($permCount > 0)
          <span style="font-size:11px;color:var(--gray-500);background:var(--gray-100);padding:3px 8px;border-radius:999px;font-weight:600">
            {{ $permCount }} {{ Str::plural('permission', $permCount) }}
          </span>
        @else
          <span style="font-size:11px;color:var(--gray-400);font-style:italic">No permissions set</span>
        @endif

        {{-- Action buttons pushed to right --}}
        <div style="margin-left:auto;display:flex;gap:6px">
          <button
            class="action-btn"
            type="button"
            onclick="openStaffAction('edit', '{{ addslashes($member->full_name) }}')"
            style="width:32px;height:32px;border-radius:var(--radius-sm)"
            title="Edit {{ $member->full_name }}">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
          </button>
          <button
            class="action-btn"
            type="button"
            onclick="openStaffAction('delete', '{{ addslashes($member->full_name) }}')"
            style="width:32px;height:32px;border-radius:var(--radius-sm);color:var(--red-600)"
            title="Delete {{ $member->full_name }}">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <polyline points="3 6 5 6 21 6"/>
              <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
              <path d="M10 11v6M14 11v6"/>
              <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
            </svg>
          </button>
        </div>
      </div>

    </div>
    @empty
    <div style="background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-lg);padding:32px 16px;text-align:center;box-shadow:var(--shadow-sm)">
      <svg width="36" height="36" fill="none" stroke="var(--gray-300)" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 10px;display:block">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      <div style="font-size:14px;color:var(--gray-500)">No staff accounts found.</div>
      <button
        type="button"
        onclick="openStaffAction('add')"
        class="primary-btn"
        style="width:auto;margin:14px auto 0;padding:10px 20px;font-size:13px;border-radius:var(--radius-md)">
        Add First Staff Member
      </button>
    </div>
    @endforelse

  </div>
</section>