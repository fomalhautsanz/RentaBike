<script>
function filterStaff(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#staff-tbody tr').forEach(r => {
    r.style.display = r.dataset.name.toLowerCase().includes(q) ? '' : 'none';
  });
}
let staffRoleFilter = '', staffStatusFilter = '';
function filterStaffRole(v) { staffRoleFilter = v; applyStaffFilters(); }
function filterStaffStatus(v) { staffStatusFilter = v; applyStaffFilters(); }
function applyStaffFilters() {
  document.querySelectorAll('#staff-tbody tr').forEach(r => {
    const roleOk = !staffRoleFilter || r.dataset.role === staffRoleFilter;
    const statusOk = !staffStatusFilter || r.dataset.status === staffStatusFilter;
    r.style.display = roleOk && statusOk ? '' : 'none';
  });
}
function openEditStaff(name, role, status) {
  document.getElementById('edit-staff-name').value = name;
  document.getElementById('edit-staff-role').value = role;
  document.getElementById('edit-staff-status').value = status;
  openModal('edit-staff-modal');
}
function openDeleteStaff(name) {
  document.getElementById('delete-staff-name-display').textContent = name;
  openModal('delete-staff-modal');
}
</script>