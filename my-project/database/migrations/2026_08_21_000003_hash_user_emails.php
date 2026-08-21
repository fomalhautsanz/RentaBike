<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email_hash', 64)->nullable()->after('email');
        });

        DB::table('users')->select('id', 'email')->orderBy('id')->each(function (object $user): void {
            $email = strtolower(trim($user->email));

            DB::table('users')->where('id', $user->id)->update([
                'email' => Crypt::encryptString($email),
                'email_hash' => hash_hmac('sha256', $email, (string) config('app.key')),
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['email']);
            $table->unique('email_hash');
        });
    }

    public function down(): void
    {
        DB::table('users')->select('id', 'email')->orderBy('id')->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update([
                'email' => Crypt::decryptString($user->email),
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['email_hash']);
            $table->dropColumn('email_hash');
            $table->unique('email');
        });
    }
};
