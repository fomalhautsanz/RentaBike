<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\HashHelper;
use App\Http\Controllers\Controller;
use App\Mail\StaffPasswordResetOtp;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class StaffPasswordResetController extends Controller
{
    private const OTP_ATTEMPT_LIMIT = 5;
    private const OTP_VALID_MINUTES = 10;
    private const REQUEST_LIMIT = 3;
    private const REQUEST_DECAY_SECONDS = 900;

    public function showRequestForm()
    {
        return view('staff.passwords.request');
    }

    public function showResetForm()
    {
        return view('staff.passwords.reset');
    }

    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:150'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $emailHash = HashHelper::email($email);
        $limiterKey = 'staff-password-reset|' . $emailHash . '|' . $request->ip();

        if (!RateLimiter::tooManyAttempts($limiterKey, self::REQUEST_LIMIT)) {
            RateLimiter::hit($limiterKey, self::REQUEST_DECAY_SECONDS);

            $staff = Staff::where('email_hash', $emailHash)
                ->where('status', 'active')
                ->first();

            if ($staff) {
                $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $now = now();

                DB::table('staff_password_reset_otps')->updateOrInsert(
                    ['staff_id' => $staff->staff_id],
                    [
                        'otp_hash' => Hash::make($otp),
                        'attempts' => 0,
                        'expires_at' => $now->copy()->addMinutes(self::OTP_VALID_MINUTES),
                        'consumed_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                Mail::to($staff->email)->send(new StaffPasswordResetOtp($otp));
            }
        }

        return redirect()
            ->route('staff.password.reset')
            ->with('status', 'If an active staff account matches that email, a verification code has been sent.')
            ->withInput(['email' => $email]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:9', 'confirmed'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $staff = Staff::where('email_hash', HashHelper::email($email))
            ->where('status', 'active')
            ->first();

        if (!$staff) {
            return $this->invalidOtpResponse($email);
        }

        $resetResult = DB::transaction(function () use ($staff, $validated): string {
            $reset = DB::table('staff_password_reset_otps')
                ->where('staff_id', $staff->staff_id)
                ->lockForUpdate()
                ->first();

            if (
                !$reset ||
                $reset->consumed_at !== null ||
                Carbon::parse($reset->expires_at)->isPast() ||
                $reset->attempts >= self::OTP_ATTEMPT_LIMIT
            ) {
                return 'invalid';
            }

            if (!Hash::check($validated['otp'], $reset->otp_hash)) {
                $attempts = $reset->attempts + 1;
                DB::table('staff_password_reset_otps')
                    ->where('staff_id', $staff->staff_id)
                    ->update([
                        'attempts' => $attempts,
                        'consumed_at' => $attempts >= self::OTP_ATTEMPT_LIMIT ? now() : null,
                        'updated_at' => now(),
                    ]);

                return 'invalid';
            }

            $staff->password_hash = Hash::make($validated['password']);
            $staff->save();

            DB::table('staff_password_reset_otps')
                ->where('staff_id', $staff->staff_id)
                ->update([
                    'consumed_at' => now(),
                    'updated_at' => now(),
                ]);

            return 'success';
        });

        if ($resetResult !== 'success') {
            return $this->invalidOtpResponse($email);
        }

        RateLimiter::clear('staff-login|' . HashHelper::email($email) . '|' . $request->ip());

        return redirect()
            ->route('staff.login')
            ->with('status', 'Your password has been updated. You can now sign in.');
    }

    private function invalidOtpResponse(string $email)
    {
        return back()
            ->withErrors(['otp' => 'The verification code is invalid or expired.'])
            ->withInput(['email' => $email]);
    }
}
