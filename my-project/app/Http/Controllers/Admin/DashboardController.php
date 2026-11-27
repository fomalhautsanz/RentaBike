<?php

// hoy gi usab nako ni 
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;   // model sa activity log table 
use App\Models\Admin;         // model sa admin table
use App\Models\Bicycle;       // model sa bicycle table
use App\Models\IssueReport;   // model sa issue report table
use App\Models\Rental;        // model sa rental table
use App\Models\Staff;         // model sa staff table 
use App\Support\AdminAccess;
use App\Support\ActivityLogger;
use App\Support\MaintenanceLogs;
use App\Support\RentalPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon; // para sa pag-handle og dates/time


class DashboardController extends Controller
{
    private const SUPER_ADMIN_PERMISSIONS = [
        'View Inventory',
        'Add Inventory',
        'Edit Inventory',
        'Delete Inventory',
        'Manage Staff',
        'Handle Maintenance',
        'Manage Pricing',
    ];

    /**
     * Redirect back to the admin dashboard, reopening whichever tab the
     * request says it came from. Every store/update method funnels its
     * redirect through here (success or failure) so behaviour stays
     * consistent even as more tabs/forms get added later.
     */
    private function backToTab(Request $request, string $fallbackTab = 'dashboard')
    {
        $tab = $request->input('current_tab', $fallbackTab);

        return redirect()->route('admin.dashboard')->with('active_tab', $tab);
    }

    public function storeStaff(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:75'],
            'last_name' => ['required', 'string', 'max:75'],
            'email' => ['required', 'email', 'max:150', 'unique:staff,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'in:Staff,Admin,Super Admin'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:100'],
            'password' => ['required', 'string', 'min:9', 'confirmed'],
            'profile_picture' => ['nullable', 'image', 'max:2048'],
        ], [
            'email.unique' => 'Check the staff list to find the existing record.',
        ]);

        if ($validator->fails()) {
            return $this->backToTab($request, 'staff')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $permissions = $validated['permissions'] ?? [];
        if ($validated['role'] === 'Super Admin') {
            abort_unless(AdminAccess::canManageSuperAdmins(), 403);
            if (!$this->hasAllSuperAdminPermissions($permissions)) {
                return $this->backToTab($request, 'staff')
                    ->withErrors(['permissions' => 'Super Admin accounts must have all permissions.'])
                    ->withInput();
            }
            $permissions = self::SUPER_ADMIN_PERMISSIONS;
        }
        if (
            $validated['role'] === 'Admin'
            && !AdminAccess::canManageSuperAdmins()
            && $this->hasAllSuperAdminPermissions($permissions)
        ) {
            return $this->backToTab($request, 'staff')
                ->withErrors(['permissions' => 'You do not have permission to grant full Super Admin access.'])
                ->withInput();
        }
        if ($validated['role'] === 'Staff') {
            $permissions = array_values(array_diff($permissions, ['Manage Staff', 'Manage Pricing']));
        }
        $permissions = $this->ensureInventoryViewPermission($permissions);

        $username = Str::before($validated['email'], '@');
        $baseUsername = $username;
        $suffix = 1;
        while (Staff::where('username', $username)->exists()) {
            $username = $baseUsername . $suffix++;
        }

        $profilePicture = $request->hasFile('profile_picture')
            ? $request->file('profile_picture')->store('staff-profiles', 'public')
            : null;
        if ($request->hasFile('profile_picture') && !is_string($profilePicture)) {
            return $this->backToTab($request, 'staff')
                ->with('error', 'Could not save the staff profile picture.');
        }

        $fullName = trim($validated['first_name'] . ' ' . $validated['last_name']);

        try {
            $staff = DB::transaction(function () use ($request, $profilePicture, $fullName, $username, $validated, $permissions): Staff {
                $staff = Staff::create([
                    // staff.admin_id must reference the admin table. A staff member with
                    // Admin-portal staff files new staff under the admin who owns them.
                    'admin_id' => auth()->user() instanceof Staff ? auth()->user()->admin_id : auth()->id(),
                    'username' => $username,
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'full_name' => $fullName,
                    'email' => Str::lower($validated['email']),
                    'phone' => $validated['phone'] ?? null,
                    'password_hash' => Hash::make($validated['password']),
                    'role' => $validated['role'],
                    'status' => 'active',
                    'profile_picture' => $profilePicture,
                    'permissions' => $permissions,
                ]);

                ActivityLogger::record(
                    $request,
                    'ADD_STAFF',
                    'Added staff account: ' . $fullName
                        . ' (' . $staff->username . ')'
                        . ' | Email: ' . $staff->email
                        . ' | Phone: ' . ActivityLogger::displayValue($staff->phone)
                        . ' | Role: ' . $staff->role
                        . ' | Status: ' . $staff->status
                        . ' | Permissions: ' . ActivityLogger::displayValue($staff->permissions)
                );

                return $staff;
            });
        } catch (\Throwable $e) {
            if ($profilePicture !== null) {
                Storage::disk('public')->delete($profilePicture);
            }

            throw $e;
        }

        return $this->backToTab($request, 'staff')
            ->with('success', 'Staff member added successfully.');
    }

    public function updateStaff(Request $request, Staff $staff)
    {
        $this->ensureNotSelfStaffAccount($request, $staff);

        $before = [
            'First name' => $staff->first_name,
            'Last name' => $staff->last_name,
            'Email' => $staff->email,
            'Phone' => $staff->phone,
            'Role' => $staff->role,
            'Status' => $staff->status,
            'Permissions' => $staff->permissions,
        ];

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:75'],
            'last_name' => ['required', 'string', 'max:75'],
            'email' => ['required', 'email', 'max:150', Rule::unique('staff', 'email')->ignore($staff->staff_id, 'staff_id')],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'in:Staff,Admin,Super Admin'],
            'status' => ['required', 'in:Active,On Leave'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:100'],
            'password' => ['nullable', 'string', 'min:9', 'confirmed'],
            'profile_picture' => ['nullable', 'image', 'max:2048'],
        ], [
            'email.unique' => 'That email address is already assigned to another staff account.',
        ]);

        if ($validator->fails()) {
            return $this->backToTab($request, 'staff')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $permissions = $validated['permissions'] ?? [];
        $isSuperAdminTarget = $staff->isSuperAdmin();
        $isSuperAdminRequest = $validated['role'] === 'Super Admin';

        if (($isSuperAdminTarget || $isSuperAdminRequest) && !AdminAccess::canManageSuperAdmins()) {
            abort(403);
        }
        if (
            $validated['role'] === 'Admin'
            && !AdminAccess::canManageSuperAdmins()
            && $this->hasAllSuperAdminPermissions($permissions)
        ) {
            return $this->backToTab($request, 'staff')
                ->withErrors(['permissions' => 'You do not have permission to grant full Super Admin access.'])
                ->withInput();
        }
        if ($isSuperAdminRequest && !$this->hasAllSuperAdminPermissions($permissions)) {
            return $this->backToTab($request, 'staff')
                ->withErrors(['permissions' => 'Super Admin accounts must have all permissions.'])
                ->withInput();
        }

        if (!$this->consumeStaffConfirmation($request, $staff, 'edit')) {
            return $this->backToTab($request, 'staff')
                ->withErrors(['staff_confirmation' => 'Confirm your password before editing this account.']);
        }

        if ($isSuperAdminRequest) {
            $permissions = self::SUPER_ADMIN_PERMISSIONS;
        }
        if ($validated['role'] === 'Staff') {
            $permissions = array_values(array_diff($permissions, ['Manage Staff', 'Manage Pricing']));
        }
        $permissions = $this->ensureInventoryViewPermission($permissions);

        $staff->first_name = $validated['first_name'];
        $staff->last_name = $validated['last_name'];
        $staff->full_name = trim($validated['first_name'] . ' ' . $validated['last_name']);
        $staff->email = Str::lower($validated['email']);
        $staff->phone = $validated['phone'] ?? null;
        $staff->role = $validated['role'];
        $staff->status = strtolower(str_replace(' ', '_', $validated['status']));
        $staff->permissions = $permissions;

        if (!empty($validated['password'])) {
            $staff->password_hash = Hash::make($validated['password']);
            // Bag-ong password = i-logout ang "remember me" devices niini nga staff
            $staff->remember_token = null;
            $staff->portal_remember_token = null;
        }

        $previousProfilePicture = $staff->profile_picture;
        $newProfilePicture = $request->hasFile('profile_picture')
            ? $request->file('profile_picture')->store('staff-profiles', 'public')
            : null;
        if ($request->hasFile('profile_picture') && !is_string($newProfilePicture)) {
            return $this->backToTab($request, 'staff')
                ->with('error', 'Could not save the staff profile picture.');
        }
        if ($newProfilePicture !== null) {
            $staff->profile_picture = $newProfilePicture;
        }

        $after = [
            'First name' => $staff->first_name,
            'Last name' => $staff->last_name,
            'Email' => $staff->email,
            'Phone' => $staff->phone,
            'Role' => $staff->role,
            'Status' => $staff->status,
            'Permissions' => $staff->permissions,
        ];
        $changes = [];
        foreach ($before as $label => $oldValue) {
            if ($before[$label] !== $after[$label]) {
                $changes[$label] = ['before' => $oldValue, 'after' => $after[$label]];
            }
        }
        if (!empty($validated['password'])) {
            $changes['Password'] = ['before' => '(unchanged)', 'after' => '(changed)'];
        }
        if ($request->hasFile('profile_picture')) {
            $changes['Profile picture'] = [
                'before' => $previousProfilePicture === null ? null : basename($previousProfilePicture),
                'after' => $newProfilePicture === null ? null : basename($newProfilePicture),
            ];
        }

        try {
            DB::transaction(function () use ($request, $staff, $changes): void {
                $staff->save();

                ActivityLogger::record(
                    $request,
                    'UPDATE_STAFF',
                    'Updated staff account: ' . $staff->full_name
                        . ' (' . $staff->username . ')'
                        . ' | Staff ID: ' . $staff->staff_id
                        . ' | Changes: ' . ActivityLogger::formatChanges($changes)
                );
            });
        } catch (\Throwable $e) {
            if ($newProfilePicture !== null) {
                Storage::disk('public')->delete($newProfilePicture);
            }

            throw $e;
        }

        if ($newProfilePicture !== null && $previousProfilePicture !== null) {
            Storage::disk('public')->delete($previousProfilePicture);
        }

        return $this->backToTab($request, 'staff')
            ->with('success', 'Staff member updated successfully.');
    }

    public function verifyStaffAction(Request $request, Staff $staff)
    {
        $this->ensureNotSelfStaffAccount($request, $staff);

        $validated = $request->validate([
            'action' => ['required', 'in:edit,delete'],
            'password' => ['required', 'string'],
        ]);

        if ($staff->isSuperAdmin() && !AdminAccess::canManageSuperAdmins()) {
            abort(403);
        }

        if (!Hash::check($validated['password'], $request->user()->password_hash)) {
            return response()->json(['message' => 'The password is incorrect.'], 422);
        }

        $request->session()->put(
            $this->staffConfirmationKey($staff, $validated['action']),
            now()->addMinutes(5)->timestamp
        );

        return response()->json(['message' => 'Password confirmed.']);
    }

    public function destroyStaff(Request $request, Staff $staff)
    {
        $this->ensureNotSelfStaffAccount($request, $staff);

        if ($staff->isSuperAdmin() && !AdminAccess::canManageSuperAdmins()) {
            abort(403);
        }

        if (!$this->consumeStaffConfirmation($request, $staff, 'delete')) {
            return response()->json(['message' => 'Confirm your password before removing this account.'], 403);
        }

        $previousStatus = $staff->status;

        DB::transaction(function () use ($request, $staff, $previousStatus): void {
            $staff->status = 'inactive';
            $staff->save();

            ActivityLogger::record(
                $request,
                'DEACTIVATE_STAFF',
                'Deactivated staff account: ' . $staff->full_name
                    . ' (' . $staff->username . ')'
                    . ' | Staff ID: ' . $staff->staff_id
                    . ' | Status: ' . $previousStatus . ' -> inactive'
            );
        });

        return response()->json(['message' => 'Staff member removed successfully.']);
    }

    private function ensureNotSelfStaffAccount(Request $request, Staff $staff): void
    {
        $actor = $request->user();

        abort_if(
            $actor instanceof Staff && (int) $actor->staff_id === (int) $staff->staff_id,
            403,
            'You cannot edit or remove your own staff account.'
        );
    }

    private function staffConfirmationKey(Staff $staff, string $action): string
    {
        return "staff_action_confirmations.{$staff->getKey()}.{$action}";
    }

    private function consumeStaffConfirmation(Request $request, Staff $staff, string $action): bool
    {
        $expiresAt = $request->session()->pull($this->staffConfirmationKey($staff, $action));

        return is_numeric($expiresAt) && (int) $expiresAt >= now()->timestamp;
    }

    // kini ang function nga ma-run pag adto ka sa admin dashboard
    public function index(Request $request)
{
    // MySQL ang nag-save sa created_at ug timestamp, so ang DB clock ang gamiton (UTC ang app).
    $now = RentalPricing::dbNow();

    $totalBikes = Bicycle::count();
    $availableBikes = Bicycle::where('condition', 'good')
        ->where('status', 'available')
        ->count();

    $activeRentals = Rental::where('status', 'active')->count();
    $underMaintenance = Bicycle::whereIn('status', ['maintenance', 'repair'])->count();

    // pila ka percent sa tanan bikes
    $shareOfBikes = fn (int $count) => $totalBikes > 0
        ? (int) round(($count / $totalBikes) * 100)
        : null;

    $completedRevenue = fn ($start, $end) => (float) Rental::where('status', 'completed')
        ->whereBetween('start_time', [$start, $end])
        ->sum('total_fee');

    // revenue karong bulana vs parehas nga mga adlaw sa miaging bulan
    $revenue = $completedRevenue($now->copy()->startOfMonth(), $now);
    $lastMonth = $now->copy()->subMonthNoOverflow();
    $lastMonthRevenue = $completedRevenue($lastMonth->copy()->startOfMonth(), $lastMonth);

    $stats = [
        'total_bikes'       => $totalBikes,
        'available_percent' => $shareOfBikes($availableBikes),
        'available_bikes'   => $availableBikes,
        'active_rentals'    => $activeRentals,
        'active_percent'    => $shareOfBikes($activeRentals),
        'under_maintenance' => $underMaintenance,
        'maintenance_percent' => $shareOfBikes($underMaintenance),
        'revenue'           => $revenue,
        'revenue_change'    => $lastMonthRevenue > 0
            ? round((($revenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : null,
    ];

    // ── Call the helper methods ──────────────────────────────────────────
    $weeklyRentals      = $this->weeklyRentalCounts();
    $revenueVsRentals   = $this->monthlyRevenueVsRentals();
    $peakHours          = $this->peakRentalHours();

    // ── Recent activity from the view ────────────────────────────────────
    $recentActivity = \DB::table('vw_staff_activity_log')
        ->latest('timestamp')
        ->limit(5)
        ->get()
        ->map(function ($log) use ($now) {
            if ($log->user_type === 'system') {
                $log->actor_name = 'System';
            }

            $log->time_ago = \Carbon\Carbon::parse($log->timestamp)
                ->diffForHumans($now, \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW);
            return $log;
        });

    $logActionFilters = [
        'rental' => ['OPEN_RENTAL', 'CLOSE_RENTAL', 'EXTEND_RENTAL'],
        'maintenance' => ['UPDATE_BIKE_STATUS', 'REPORT_ISSUE', 'UPDATE_ISSUE_STATUS'],
        'inventory' => ['ADD_BIKE', 'UPDATE_BIKE', 'DELETE_BIKE'],
        'pricing' => ['UPDATE_PRICING'],
        'staff' => ['ADD_STAFF', 'UPDATE_STAFF', 'DEACTIVATE_STAFF'],
        'payment' => ['PAYMENT_SETTLED'],
        'reports' => ['GENERATE_REPORT'],
    ];

    $logFilters = $request->validate([
        'log_action' => ['nullable', Rule::in(array_keys($logActionFilters))],
        'log_period' => ['nullable', Rule::in(['all', '24', '168', '720', 'custom'])],
        'log_user_type' => ['nullable', Rule::in(['admin', 'staff', 'system'])],
        'log_from' => ['nullable', 'required_if:log_period,custom', 'date'],
        'log_to' => ['nullable', 'required_if:log_period,custom', 'date', 'after_or_equal:log_from'],
    ]);

    $activityLogsQuery = ActivityLog::query();

    if (!empty($logFilters['log_action'])) {
        $activityLogsQuery->whereIn('action', $logActionFilters[$logFilters['log_action']]);
    }

    if (!empty($logFilters['log_user_type'])) {
        $activityLogsQuery->where('user_type', $logFilters['log_user_type']);
    }

    $logPeriod = $logFilters['log_period'] ?? 'all';
    if (in_array($logPeriod, ['24', '168', '720'], true)) {
        $activityLogsQuery->where('timestamp', '>=', now()->subHours((int) $logPeriod));
    } elseif ($logPeriod === 'custom') {
        $activityLogsQuery
            ->whereDate('timestamp', '>=', $logFilters['log_from'])
            ->whereDate('timestamp', '<=', $logFilters['log_to']);
    }

    $activityLogs = $activityLogsQuery
        ->orderByDesc('timestamp')
        ->orderByDesc('log_id')
        ->paginate(50)
        ->withQueryString();

    $logs = $activityLogs->getCollection();
    $adminNames = Admin::whereIn('admin_id', $logs->where('user_type', 'admin')->pluck('user_id')->unique())
        ->pluck('full_name', 'admin_id');
    $staffMembers = Staff::whereIn('staff_id', $logs->where('user_type', 'staff')->pluck('user_id')->unique())
        ->get(['staff_id', 'full_name', 'first_name', 'last_name'])
        ->keyBy('staff_id');

    $logs->each(function (ActivityLog $log) use ($adminNames, $staffMembers) {
        $staffMember = $staffMembers->get($log->user_id);
        $actorName = match ($log->user_type) {
            'admin' => $adminNames->get($log->user_id, 'Unknown Admin'),
            'staff' => $staffMember
                ? (trim(($staffMember->first_name ?? '') . ' ' . ($staffMember->last_name ?? '')) ?: $staffMember->full_name)
                : 'Unknown Staff',
            default => 'System',
        };

        $log->setAttribute('display_actor_name', $actorName);
    });

    // ── Bike type distribution ───────────────────────────────────────────
    $bikeTypeDistribution = Bicycle::selectRaw('bike_type, COUNT(*) as count')
        ->groupBy('bike_type')
        ->pluck('count', 'bike_type')
        ->toArray();

    $staff = Staff::where('status', '!=', 'inactive')->orderBy('staff_id')->get()->map(function ($member) {
        $name = trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: $member->full_name;

        return (object) [
            'id'          => $member->staff_id,
            'name'        => $name,
            'first_name'  => $member->first_name ?: Str::before($name, ' '),
            'last_name'   => $member->last_name ?: Str::after($name, ' '),
            'email'       => $member->email ?? 'N/A',
            'phone'       => $member->phone ?? 'N/A',
            'role'        => $member->role ?? 'Staff',
            'status'      => ucwords(str_replace('_', ' ', strtolower($member->status ?? 'active'))),
            'profile_picture' => $member->profile_picture,
            'permissions' => array_values(array_diff(
                $member->permissions ?? ['View Inventory'],
                ['Process Rentals', 'View Reports']
            )),
            'is_staff'    => true,
            'is_super_admin' => $member->isSuperAdmin(),
            'is_main_admin' => false,
        ];
    })->toBase(); // downgrade to plain Support Collection so merge() doesn't call getKey() on stdClass

    $admins = Admin::orderBy('admin_id')->get()->map(function ($admin) {
        return (object) [
            'id'          => $admin->admin_id,
            'name'        => $admin->full_name,
            'first_name'  => Str::before($admin->full_name, ' '),
            'last_name'   => Str::after($admin->full_name, ' '),
            'email'       => $admin->email ?? 'N/A',
            'phone'       => $admin->phone ?? 'N/A',
            'role'        => 'Admin',
            'status'      => 'Active',
            'permissions' => [],
            'is_staff'    => false,
            'is_main_admin' => true,
        ];
    })->toBase(); // downgrade to plain Support Collection so merge() doesn't call getKey() on stdClass


    $activeRentalsByBike = Rental::with('payment')
        ->where('status', 'active')
        ->get()
        ->keyBy('bike_id');
    $lastMaintenanceByBike = MaintenanceLogs::lastMaintenanceByBike();
    $openConditionByBike = MaintenanceLogs::openConditionByBike();
    $bikes = Bicycle::orderBy('bike_id')->get()->map(function (Bicycle $bike) use ($activeRentalsByBike, $lastMaintenanceByBike, $openConditionByBike, $now) {
        $rental = $activeRentalsByBike->get($bike->bike_id);
        $summary = $rental ? RentalPricing::summary($rental, $bike->bike_type, $now) : null;
        $condition = $openConditionByBike->get($bike->bike_id, $bike->condition);

        return (object) [
            'bike_code' => $bike->qr_code,
            'name' => trim($bike->model . ' · ' . $bike->make),
            'type' => $bike->bike_type,
            'qr_code' => $bike->qr_code,
            'status' => $bike->status === 'repair' ? 'Maintenance' : ucfirst($bike->status),
            'condition' => $condition === 'repair' ? 'Needs Repair' : ucfirst($condition),
            'last_maintenance' => $lastMaintenanceByBike->has($bike->bike_id)
                ? Carbon::parse($lastMaintenanceByBike->get($bike->bike_id))
                : null,
            'overdue' => $summary['overdue'] ?? false,
            'estimated_overtime_fee' => $summary['estimated_overtime_fee'] ?? 0.0,
        ];
    });
    $staff = $staff->merge($admins);
    // Maintenance logs: real data gikan sa issue_report + filters (logic naa sa MaintenanceLogs)
    $maintenanceFilters = MaintenanceLogs::validateFilters($request);
    $reports = MaintenanceLogs::filtered($maintenanceFilters)->get()
        ->map(fn (IssueReport $report) => MaintenanceLogs::present($report));

    $pendingReports    = IssueReport::where('status', 'pending')->count();
    $inProgressReports = IssueReport::where('status', 'in_progress')->count();
    $resolvedReports   = IssueReport::where('status', 'resolved')->count();

    $nextBikeCode = Bicycle::nextQrCode('Mountain Bike');

    $pricingSettings = AdminAccess::allows('Manage Pricing')
        ? RentalPricing::adminSettings()
        : [];

    return view('admin.dashboard', compact(
        'staff',
        'stats',
        'pendingReports', 'inProgressReports', 'resolvedReports',
        'recentActivity', 'bikeTypeDistribution',
        'weeklyRentals', 'revenueVsRentals', 'peakHours', 'bikes',
        'nextBikeCode', 'activityLogs', 'logActionFilters', 'logFilters',
        'reports', 'maintenanceFilters', 'pricingSettings'
    ));
}


   public function storeBike(Request $request)
{
    $validator = Validator::make($request->all(), [
        'model' => ['required', 'string', 'max:100'],
        'make' => ['required', 'string', 'max:100'],
        'bike_type' => ['required', 'string', 'in:' . implode(',', Bicycle::bikeTypes())],
        'condition' => ['required', 'in:good,repair,missing'],
    ]);

    if ($validator->fails()) {
        return $this->backToTab($request, 'bikes')
            ->withErrors($validator)
            ->withInput();
    }

    $validated = $validator->validated();

    $bike = DB::transaction(function () use ($request, $validated): Bicycle {
        $bike = Bicycle::create([
            ...$validated,
            'qr_code' => Bicycle::reserveNextQrCode($validated['bike_type']),
            'status' => $validated['condition'] === 'good' ? 'available' : 'repair',
        ]);

        ActivityLogger::record(
            $request,
            'ADD_BIKE',
            'Added bike: ' . $bike->model . ' ' . $bike->make
                . ' (' . $bike->qr_code . ')'
                . ' | Bike ID: ' . $bike->qr_code
                . ' | Database ID: ' . $bike->bike_id
                . ' | Bike type: ' . $bike->bike_type
                . ' | Condition: ' . $bike->condition
                . ' | Status: ' . $bike->status
        );

        return $bike;
    });

    return $this->backToTab($request, 'bikes')
        ->with('success', 'Bike added successfully.');
}

    public function nextBikeCode(Request $request)
    {
        $validated = $request->validate([
            'bike_type' => ['required', 'string', 'in:' . implode(',', Bicycle::bikeTypes())],
            'exclude_qr_code' => ['nullable', 'string', 'exists:bicycle,qr_code'],
        ]);

        return response()->json([
            'bike_code' => Bicycle::nextQrCode($validated['bike_type']),
        ]);
    }

    public function updateBike(Request $request, Bicycle $bike)
    {
        if (!$this->hasBikeConfirmation($request, $bike, 'edit')) {
            return $this->backToTab($request, 'bikes')
                ->withErrors(['bike_confirmation' => 'Confirm your password before editing this bike.']);
        }

        $validator = Validator::make($request->all(), [
            'model' => ['required', 'string', 'max:100'],
            'make' => ['required', 'string', 'max:100'],
            'bike_type' => ['required', 'string', Rule::in(array_unique([
                ...Bicycle::bikeTypes(),
                $bike->bike_type,
            ]))],
            'status' => ['nullable', 'in:available,rented,repair'],
            'condition' => ['required', 'in:good,repair,missing,other'],
        ]);

        if ($validator->fails()) {
            return $this->backToTab($request, 'bikes')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $status = $validated['status'] ?? ($bike->status === 'rented' ? 'rented' : 'available');
        $hasActiveRental = false;
        if ($status === 'rented' || $bike->status === 'rented') {
            $hasActiveRental = Rental::where('bike_id', $bike->bike_id)
                ->where('status', 'active')
                ->exists();
        }

        if ($status === 'rented' && ! $hasActiveRental) {
            return $this->backToTab($request, 'bikes')
                ->withErrors(['status' => 'Create a rental before marking this bike as rented.']);
        }

        if ($hasActiveRental && ($status !== 'rented' || $validated['condition'] !== 'good')) {
            return $this->backToTab($request, 'bikes')
                ->withErrors(['status' => "End the active rental before changing this bike's status or condition."]);
        }

        $before = [
            'Model' => $bike->model,
            'Make' => $bike->make,
            'Bike type' => $bike->bike_type,
            'Bike code' => $bike->qr_code,
            'Status' => $bike->status,
            'Condition' => $bike->condition,
        ];

        $condition = $validated['condition'];
        if ($status === 'repair' && $condition === 'good') {
            $condition = 'repair';
        }

        if ($condition !== 'good') {
            $status = 'repair';
        } elseif ($status === 'repair') {
            $status = $bike->status === 'rented' ? 'rented' : 'available';
        }

        DB::transaction(function () use ($request, $bike, $validated, $status, $condition, $before): void {
            $bike->update([
                'model' => $validated['model'],
                'make' => $validated['make'],
                'bike_type' => $validated['bike_type'],
                'qr_code' => $bike->bike_type === $validated['bike_type']
                    ? $bike->qr_code
                    : Bicycle::reserveNextQrCode($validated['bike_type']),
                'status' => $status,
                'condition' => $condition,
            ]);

            $after = [
                'Model' => $bike->model,
                'Make' => $bike->make,
                'Bike type' => $bike->bike_type,
                'Bike code' => $bike->qr_code,
                'Status' => $bike->status,
                'Condition' => $bike->condition,
            ];
            $changes = [];
            foreach ($before as $label => $oldValue) {
                if ($oldValue !== $after[$label]) {
                    $changes[$label] = ['before' => $oldValue, 'after' => $after[$label]];
                }
            }

            ActivityLogger::record(
                $request,
                'UPDATE_BIKE',
                'Updated bike: ' . $bike->model . ' ' . $bike->make
                    . ' (' . $bike->qr_code . ')'
                    . ' | Bike ID: ' . $bike->qr_code
                    . ' | Database ID: ' . $bike->bike_id
                    . ' | Changes: ' . ActivityLogger::formatChanges($changes)
            );
        });

        $this->clearBikeConfirmation($request, $bike, 'edit');

        return $this->backToTab($request, 'bikes')
            ->with('success', 'Bike updated successfully.');
    }

    public function destroyBike(Request $request, Bicycle $bike)
    {
        if (!$this->hasBikeConfirmation($request, $bike, 'delete')) {
            return $this->backToTab($request, 'bikes')
                ->withErrors(['bike_confirmation' => 'Confirm your password before deleting this bike.']);
        }

        if ($bike->status === 'rented') {
            return $this->backToTab($request, 'bikes')
                ->with('error', 'A rented bike cannot be removed from inventory.');
        }

        DB::transaction(function () use ($request, $bike): void {
            $bike->delete();

            ActivityLogger::record(
                $request,
                'DELETE_BIKE',
                'Deleted bike: ' . $bike->model . ' ' . $bike->make
                    . ' (' . $bike->qr_code . ')'
                    . ' | Bike ID: ' . $bike->qr_code
                    . ' | Database ID: ' . $bike->bike_id
                    . ' | Bike type: ' . $bike->bike_type
                    . ' | Condition: ' . $bike->condition
                    . ' | Status: ' . $bike->status
            );
        });
        $this->clearBikeConfirmation($request, $bike, 'delete');

        return $this->backToTab($request, 'bikes')
            ->with('success', 'Bike removed from inventory.');
    }

    public function verifyBikeAction(Request $request, Bicycle $bike)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:edit,delete'],
            'password' => ['required', 'string'],
        ]);

        $privilege = $validated['action'] === 'edit' ? 'Edit Inventory' : 'Delete Inventory';
        if (!AdminAccess::allows($privilege)) {
            return response()->json(['message' => "You do not have the \"{$privilege}\" privilege."], 403);
        }

        if (!Hash::check($validated['password'], $request->user()->password_hash)) {
            return response()->json(['message' => 'The password is incorrect.'], 422);
        }

        $request->session()->put(
            $this->bikeConfirmationKey($bike, $validated['action']),
            now()->addMinutes(5)->timestamp
        );

        return response()->json(['message' => 'Password confirmed.']);
    }

    private function bikeConfirmationKey(Bicycle $bike, string $action): string
    {
        return "bike_action_confirmations.{$bike->getKey()}.{$action}";
    }

    private function hasBikeConfirmation(Request $request, Bicycle $bike, string $action): bool
    {
        $expiresAt = $request->session()->get($this->bikeConfirmationKey($bike, $action));

        return is_numeric($expiresAt) && (int) $expiresAt >= now()->timestamp;
    }

    private function clearBikeConfirmation(Request $request, Bicycle $bike, string $action): void
    {
        $request->session()->forget($this->bikeConfirmationKey($bike, $action));
    }

    private function normalizePermissions(mixed $permissions): array
    {
        if (is_string($permissions)) {
            $decoded = json_decode($permissions, true);
            $permissions = is_array($decoded) ? $decoded : explode(',', $permissions);
        }

        if (!is_array($permissions)) {
            return ['View Inventory', 'Process Rentals', 'View Reports'];
        }

        return array_values(array_filter(array_map(
            static fn ($permission) => is_string($permission) ? trim($permission) : null,
            $permissions
        )));
    }

    private function ensureInventoryViewPermission(array $permissions): array
    {
        if (array_intersect(['Add Inventory', 'Edit Inventory', 'Delete Inventory'], $permissions)) {
            $permissions[] = 'View Inventory';
        }

        return array_values(array_unique($permissions));
    }

    private function hasAllSuperAdminPermissions(array $permissions): bool
    {
        return array_diff(self::SUPER_ADMIN_PERMISSIONS, $permissions) === [];
    }

    // Rental counts for the current Monday-to-Sunday week.
    protected function weeklyRentalCounts(?Carbon $now = null): array
    {
        $now ??= RentalPricing::dbNow();
        $start = $now->copy()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addDays(6)->endOfDay();
        $queryEnd = $now->lt($end) ? $now : $end;

        $counts = Rental::selectRaw('DATE(start_time) as day, COUNT(*) as total')
            ->whereBetween('start_time', [$start, $queryEnd])
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $dates = [];
        $data = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $labels[] = $date->format('D j');
            $dates[] = $date->format('F j, Y');
            $data[] = (int) ($counts[$date->format('Y-m-d')] ?? 0);
        }

        return [
            'labels' => $labels,
            'dates' => $dates,
            'period' => 'Week ' . $start->isoWeek . ' · ' . $start->format('F j, Y')
                . ' – ' . $end->format('F j, Y'),
            'data' => $data,
        ];
    }

   // completed rentals, revenue, and rental count for the last 5 months
    protected function monthlyRevenueVsRentals(): array
    {
        $now = RentalPricing::dbNow();
        $labels = [];
        $revenue = [];
        $rentalCounts = [];

        // loops 4 months ago up to now 
        for ($i = 4; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);

            // kuhaon ang sum sa total free and count sa rentals in a yr or month 
            $rows = Rental::where('status', 'completed')
                ->whereMonth('start_time', $month->month)
                ->whereYear('start_time', $month->year)
                ->selectRaw('COALESCE(SUM(total_fee), 0) as revenue, COUNT(*) as total')
                ->first();

            $labels[] = $month->format('M'); 
            $revenue[] = (float) $rows->revenue;
            $rentalCounts[] = (int) $rows->total;
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'rentals' => $rentalCounts];
    }

   // rental counts grouped by 2-hour block, based on sa start 
    protected function peakRentalHours(): array
    {
        // ihap tanan rentals per 2-hour block (e.g. 7AM goes to the 6AM block)
        $counts = Rental::selectRaw('FLOOR(HOUR(start_time) / 2) * 2 as hr, COUNT(*) as total')
            ->groupBy('hr')
            ->pluck('total', 'hr');

        // fixed hrs 
        $blocks = [6, 8, 10, 12, 14, 16, 18, 20];
        $labels = [];
        $data = [];

        foreach ($blocks as $hour) {
            $suffix = $hour < 12 ? 'AM' : 'PM';
            $display = $hour > 12 ? $hour - 12 : $hour;
            $labels[] = "{$display}{$suffix}";
            $data[] = (int) ($counts[$hour] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }
}