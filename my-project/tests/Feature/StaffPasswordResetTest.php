<?php

use App\Mail\StaffPasswordResetOtp;
use App\Models\Staff;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('staff_password_reset_otps');
    Schema::dropIfExists('staff');

    Schema::create('staff', function (Blueprint $table) {
        $table->increments('staff_id');
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

    Schema::create('staff_password_reset_otps', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('staff_id')->unique();
        $table->string('otp_hash');
        $table->unsignedTinyInteger('attempts')->default(0);
        $table->dateTime('expires_at');
        $table->dateTime('consumed_at')->nullable();
        $table->timestamps();
    });

    $this->staff = Staff::create([
        'username' => 'staff',
        'password_hash' => Hash::make('old-password'),
        'full_name' => 'Test Staff',
        'first_name' => 'Test',
        'last_name' => 'Staff',
        'email' => 'staff@example.test',
        'role' => 'Staff',
        'status' => 'active',
    ]);

    Mail::fake();
});

test('staff can reset their password with an emailed otp', function () {
    $this->withoutVite()
        ->post(route('staff.password.email'), ['email' => 'staff@example.test'])
        ->assertRedirect(route('staff.password.reset'))
        ->assertSessionHas('status');

    Mail::assertSent(StaffPasswordResetOtp::class);
    $otp = Mail::sent(StaffPasswordResetOtp::class)->first()->code;

    $this->post(route('staff.password.update'), [
        'email' => 'staff@example.test',
        'otp' => $otp,
        'password' => 'a-new-password',
        'password_confirmation' => 'a-new-password',
    ])->assertRedirect(route('staff.login'))
        ->assertSessionHas('status');

    expect(Hash::check('a-new-password', $this->staff->fresh()->password_hash))->toBeTrue();
    expect(DB::table('staff_password_reset_otps')->where('staff_id', $this->staff->staff_id)->value('consumed_at'))
        ->not->toBeNull();
});

test('staff password reset rejects the code after five incorrect attempts', function () {
    $this->withoutVite()->post(route('staff.password.email'), ['email' => 'staff@example.test']);
    $otp = Mail::sent(StaffPasswordResetOtp::class)->first()->code;

    foreach (range(1, 5) as $attempt) {
        $this->post(route('staff.password.update'), [
            'email' => 'staff@example.test',
            'otp' => $otp === '000000' ? '000001' : '000000',
            'password' => 'a-new-password',
            'password_confirmation' => 'a-new-password',
        ])->assertSessionHasErrors('otp');
    }

    expect(DB::table('staff_password_reset_otps')->where('staff_id', $this->staff->staff_id)->value('attempts'))
        ->toBe(5);

    $this->post(route('staff.password.update'), [
        'email' => 'staff@example.test',
        'otp' => $otp,
        'password' => 'a-new-password',
        'password_confirmation' => 'a-new-password',
    ])->assertSessionHasErrors('otp');

    expect(Hash::check('old-password', $this->staff->fresh()->password_hash))->toBeTrue();
});

test('password reset requests do not reveal whether a staff email exists', function () {
    $response = $this->post(route('staff.password.email'), ['email' => 'unknown@example.test']);

    $response->assertRedirect(route('staff.password.reset'))
        ->assertSessionHas('status', 'If an active staff account matches that email, a verification code has been sent.');
    Mail::assertNothingSent();
});
