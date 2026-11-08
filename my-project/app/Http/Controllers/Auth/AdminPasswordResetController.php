<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\HashHelper;
use App\Http\Controllers\Controller;
use App\Mail\AdminPasswordResetOtp;
use App\Models\Admin;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Admin Portal "forgot password" flow. Mirrors StaffPasswordResetController:
 * a hashed six-digit OTP is emailed, expires after 10 minutes, and is
 * burned after five wrong attempts. Responses never reveal whether an
 * account exists for the submitted email.
 *
 * Covers everyone who can sign in at /admin/login: accounts in the admin
 * table, and active staff accounts whose role is "Admin".
 */
class AdminPasswordResetController extends Controller
{
    private const OTP_ATTEMPT_LIMIT = 5;
    private const OTP_VALID_MINUTES = 10;
    private const REQUEST_LIMIT = 3;
    private const REQUEST_DECAY_SECONDS = 900;

    public const SENT_MESSAGE = 'If an admin account matches that email, a verification code has been sent.';

    public function showRequestForm()
    {
        if ($this->alreadySignedIn()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.passwords.request');
    }

    public function showResetForm()
    {
        if ($this->alreadySignedIn()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.passwords.reset');
    }

    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:150'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $emailHash = HashHelper::email($email);
        $limiterKey = 'admin-password-reset|' . $emailHash . '|' . $request->ip();

        if (!RateLimiter::tooManyAttempts($limiterKey, self::REQUEST_LIMIT)) {
            RateLimiter::hit($limiterKey, self::REQUEST_DECAY_SECONDS);

            $account = $this->findAccount($emailHash);

            if ($account) {
                [$user, $table, $keyColumn] = $account;
                $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $now = now();

                DB::table($table)->updateOrInsert(
                    [$keyColumn => $user->getKey()],
                    [
                        'otp_hash' => Hash::make($otp),
                        'attempts' => 0,
                        'expires_at' => $now->copy()->addMinutes(self::OTP_VALID_MINUTES),
                        'consumed_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                Mail::to($user->email)->send(new AdminPasswordResetOtp($otp));
            }
        }

        return redirect()
            ->route('admin.password.reset')
            ->with('status', self::SENT_MESSAGE)
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
        $account = $this->findAccount(HashHelper::email($email));

        if (!$account) {
            return $this->invalidOtpResponse($email);
        }

        [$user, $table, $keyColumn] = $account;

        $resetResult = DB::transaction(function () use ($user, $table, $keyColumn, $validated): string {
            $reset = DB::table($table)
                ->where($keyColumn, $user->getKey())
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
                DB::table($table)
                    ->where($keyColumn, $user->getKey())
                    ->update([
                        'attempts' => $attempts,
                        'consumed_at' => $attempts >= self::OTP_ATTEMPT_LIMIT ? now() : null,
                        'updated_at' => now(),
                    ]);

                return 'invalid';
            }

            $user->password_hash = Hash::make($validated['password']);
            $user->save();

            DB::table($table)
                ->where($keyColumn, $user->getKey())
                ->update([
                    'consumed_at' => now(),
                    'updated_at' => now(),
                ]);

            return 'success';
        });

        if ($resetResult !== 'success') {
            return $this->invalidOtpResponse($email);
        }

        // Lift any login lockout caused by attempts with the forgotten password.
        $emailHash = HashHelper::email($email);
        RateLimiter::clear('admin-login|' . $emailHash . '|' . $request->ip());
        RateLimiter::clear('staff-login|' . $emailHash . '|' . $request->ip());

        return redirect()
            ->route('login')
            ->with('status', 'Your admin password has been updated. You can now sign in.');
    }

    /**
     * Resolve the Admin Portal account for an email hash, using the same
     * precedence as the admin sign-in: the admin table first, then active
     * staff with role "Admin". Returns [model, otp table, otp key column].
     *
     * @return array{0: Model, 1: string, 2: string}|null
     */
    private function findAccount(string $emailHash): ?array
    {
        $admin = Admin::where('email_hash', $emailHash)->first();
        if ($admin) {
            return [$admin, 'admin_password_reset_otps', 'admin_id'];
        }

        $staff = Staff::where('email_hash', $emailHash)->adminPortalEligible()->first();
        if ($staff) {
            // Staff-table accounts keep their codes in the staff OTP table.
            return [$staff, 'staff_password_reset_otps', 'staff_id'];
        }

        return null;
    }

    private function alreadySignedIn(): bool
    {
        return Auth::guard('web')->check()
            || (bool) Auth::guard('staff_admin')->user()?->hasAdminPortalAccess();
    }

    private function invalidOtpResponse(string $email)
    {
        return back()
            ->withErrors(['otp' => 'The verification code is invalid or expired.'])
            ->withInput(['email' => $email]);
    }
}
