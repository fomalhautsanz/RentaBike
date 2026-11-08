<?php

use App\Http\Controllers\Auth\AdminPasswordResetController;
use App\Mail\AdminPasswordResetOtp;
use App\Models\Admin;
use App\Models\Staff;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach (['staff_password_reset_otps', 'admin_password_reset_otps', 'staff', 'admin'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('admin', function (Blueprint $table) {
        $table->increments('admin_id');
        $table->string('username')->unique();
        $table->string('password_hash');
        $table->string('full_name');
        $table->string('email');
        $table->string('email_hash')->nullable();
        $table->dateTime('created_at')->useCurrent();
    });

    Schema::create('staff', function (Blueprint $table) {
        $table->increments('staff_id');
        $table->unsignedInteger('admin_id')->nullable();
        $table->string('username')->unique();
        $table->string('password_hash');
        $table->string('full_name');
        $table->string('first_name')->nullable();
        $table->string('last_name')->nullable();
        $table->string('email');
        $table->string('email_hash')->nullable();
        $table->string('role');
        $table->string('status');
    });

    foreach (['admin_password_reset_otps' => 'admin_id', 'staff_password_reset_otps' => 'staff_id'] as $name => $key) {
        Schema::create($name, function (Blueprint $table) use ($key) {
            $table->id();
            $table->unsignedInteger($key)->unique();
            $table->string('otp_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('expires_at');
            $table->dateTime('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    $this->admin = Admin::create([
        'username' => 'owner',
        'password_hash' => Hash::make('owner-password'),
        'full_name' => 'Owner Admin',
        'email' => 'owner@example.test',
    ]);

    $makeStaff = fn (string $email, string $role, string $status = 'active') => Staff::create([
        'admin_id' => $this->admin->admin_id,
        'username' => strtok($email, '@'),
        'password_hash' => Hash::make('staff-password'),
        'full_name' => ucfirst(strtok($email, '@')),
        'email' => $email,
        'role' => $role,
        'status' => $status,
    ]);

    $this->staffAdmin = $makeStaff('avianna@example.test', 'Admin');
    $this->plainStaff = $makeStaff('plain@example.test', 'Staff');
    $this->inactiveAdmin = $makeStaff('inactive@example.test', 'Admin', 'inactive');

    Route::middleware(['web', 'admin.auth'])->get('/_test/admin-area', fn () => 'admin:' . auth()->user()->email);

    Mail::fake();
});

test('staff with the Admin role can sign in to the admin portal', function () {
    $this->post(route('admin.login.submit'), [
        'email' => 'avianna@example.test',
        'password' => 'staff-password',
    ])->assertRedirect(route('admin.dashboard'));

    expect(Auth::guard('staff_admin')->id())->toBe($this->staffAdmin->staff_id);
    expect(Auth::guard('web')->check())->toBeFalse();

    $this->get('/_test/admin-area')->assertOk()->assertSee('admin:avianna@example.test');
});

test('staff with the Staff role or an inactive status cannot sign in to the admin portal', function (string $email) {
    $this->post(route('admin.login.submit'), [
        'email' => $email,
        'password' => 'staff-password',
    ])->assertSessionHasErrors('email');

    expect(Auth::guard('staff_admin')->check())->toBeFalse();
    $this->get('/_test/admin-area')->assertRedirect(route('login'));
})->with(['plain@example.test', 'inactive@example.test']);

test('demoting a signed-in staff admin revokes admin portal access', function () {
    $this->post(route('admin.login.submit'), [
        'email' => 'avianna@example.test',
        'password' => 'staff-password',
    ]);
    $this->get('/_test/admin-area')->assertOk();

    $this->staffAdmin->update(['role' => 'Staff']);

    $this->get('/_test/admin-area')->assertRedirect(route('login'));
    expect(Auth::guard('staff_admin')->check())->toBeFalse();
});

test('admin table accounts still sign in as before', function () {
    $this->post(route('admin.login.submit'), [
        'email' => 'owner@example.test',
        'password' => 'owner-password',
    ])->assertRedirect(route('admin.dashboard'));

    expect(Auth::guard('web')->id())->toBe($this->admin->admin_id);
    $this->get('/_test/admin-area')->assertOk()->assertSee('admin:owner@example.test');
});

test('staff with the Admin role can reset their password from the admin portal', function () {
    $this->withoutVite()
        ->post(route('admin.password.email'), ['email' => 'avianna@example.test'])
        ->assertRedirect(route('admin.password.reset'))
        ->assertSessionHas('status', AdminPasswordResetController::SENT_MESSAGE);

    Mail::assertSent(AdminPasswordResetOtp::class, fn ($mail) => $mail->hasTo('avianna@example.test'));
    $otp = Mail::sent(AdminPasswordResetOtp::class)->first()->code;

    expect(DB::table('staff_password_reset_otps')->where('staff_id', $this->staffAdmin->staff_id)->exists())->toBeTrue();
    expect(DB::table('admin_password_reset_otps')->count())->toBe(0);

    $this->post(route('admin.password.update'), [
        'email' => 'avianna@example.test',
        'otp' => $otp,
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertRedirect(route('login'));

    expect(Hash::check('brand-new-password', $this->staffAdmin->fresh()->password_hash))->toBeTrue();

    $this->post(route('admin.login.submit'), [
        'email' => 'avianna@example.test',
        'password' => 'brand-new-password',
    ])->assertRedirect(route('admin.dashboard'));
});

test('the admin portal reset does not send codes to Staff-role accounts', function () {
    $this->post(route('admin.password.email'), ['email' => 'plain@example.test'])
        ->assertSessionHas('status', AdminPasswordResetController::SENT_MESSAGE);

    Mail::assertNothingSent();
});
