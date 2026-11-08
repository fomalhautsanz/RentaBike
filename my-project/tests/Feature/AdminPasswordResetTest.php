<?php

use App\Http\Controllers\Auth\AdminPasswordResetController;
use App\Mail\AdminPasswordResetOtp;
use App\Models\Admin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('admin_password_reset_otps');
    Schema::dropIfExists('admin');
    Schema::dropIfExists('staff');

    // The admin reset also checks staff accounts with role "Admin".
    Schema::create('staff', function (Blueprint $table) {
        $table->increments('staff_id');
        $table->string('email');
        $table->string('email_hash')->nullable();
        $table->string('role');
        $table->string('status');
    });

    Schema::create('admin', function (Blueprint $table) {
        $table->increments('admin_id');
        $table->string('username')->unique();
        $table->string('password_hash');
        $table->string('full_name');
        $table->string('email');
        $table->string('email_hash')->nullable();
        $table->dateTime('created_at')->useCurrent();
    });

    Schema::create('admin_password_reset_otps', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('admin_id')->unique();
        $table->string('otp_hash');
        $table->unsignedTinyInteger('attempts')->default(0);
        $table->dateTime('expires_at');
        $table->dateTime('consumed_at')->nullable();
        $table->timestamps();
    });

    $this->admin = Admin::create([
        'username' => 'admin',
        'password_hash' => Hash::make('old-password'),
        'full_name' => 'Test Admin',
        'email' => 'admin@example.test',
    ]);

    Mail::fake();
});

test('admin password pages render', function () {
    $this->withoutVite()->get(route('admin.password.request'))->assertOk()->assertSee('Forgot your password?');
    $this->withoutVite()->get(route('admin.password.reset'))->assertOk()->assertSee('Reset your password');
});

test('admin can reset their password with an emailed otp', function () {
    $this->withoutVite()
        ->post(route('admin.password.email'), ['email' => 'Admin@Example.test'])
        ->assertRedirect(route('admin.password.reset'))
        ->assertSessionHas('status', AdminPasswordResetController::SENT_MESSAGE);

    Mail::assertSent(AdminPasswordResetOtp::class, fn ($mail) => $mail->hasTo('admin@example.test'));
    $otp = Mail::sent(AdminPasswordResetOtp::class)->first()->code;

    $this->post(route('admin.password.update'), [
        'email' => 'admin@example.test',
        'otp' => $otp,
        'password' => 'a-new-password',
        'password_confirmation' => 'a-new-password',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('status');

    expect(Hash::check('a-new-password', $this->admin->fresh()->password_hash))->toBeTrue();

    // A used code cannot be replayed.
    $this->post(route('admin.password.update'), [
        'email' => 'admin@example.test',
        'otp' => $otp,
        'password' => 'another-password',
        'password_confirmation' => 'another-password',
    ])->assertSessionHasErrors('otp');
});

test('admin password reset rejects the code after five incorrect attempts', function () {
    $this->withoutVite()->post(route('admin.password.email'), ['email' => 'admin@example.test']);
    $otp = Mail::sent(AdminPasswordResetOtp::class)->first()->code;

    foreach (range(1, 5) as $attempt) {
        $this->post(route('admin.password.update'), [
            'email' => 'admin@example.test',
            'otp' => $otp === '000000' ? '000001' : '000000',
            'password' => 'a-new-password',
            'password_confirmation' => 'a-new-password',
        ])->assertSessionHasErrors('otp');
    }

    expect(DB::table('admin_password_reset_otps')->where('admin_id', $this->admin->admin_id)->value('attempts'))
        ->toBe(5);

    $this->post(route('admin.password.update'), [
        'email' => 'admin@example.test',
        'otp' => $otp,
        'password' => 'a-new-password',
        'password_confirmation' => 'a-new-password',
    ])->assertSessionHasErrors('otp');

    expect(Hash::check('old-password', $this->admin->fresh()->password_hash))->toBeTrue();
});

test('admin reset requests do not reveal whether an email exists', function () {
    $this->post(route('admin.password.email'), ['email' => 'unknown@example.test'])
        ->assertRedirect(route('admin.password.reset'))
        ->assertSessionHas('status', AdminPasswordResetController::SENT_MESSAGE);

    Mail::assertNothingSent();
});
