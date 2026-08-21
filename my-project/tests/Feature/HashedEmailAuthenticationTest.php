<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('user emails are encrypted and indexed by a keyed hash', function () {
    $user = User::factory()->create(['email' => ' Staff@RentaBike.com ']);

    expect($user->email)->toBe('staff@rentabike.com')
        ->and($user->getRawOriginal('email'))->not->toBe('staff@rentabike.com')
        ->and($user->email_hash)->toBe(User::emailLookupHash('staff@rentabike.com'));
});

test('staff can log in with a normalized email', function () {
    User::factory()->create([
        'email' => 'staff@rentabike.com',
        'password' => Hash::make('secret-password'),
    ]);

    $response = $this->post('/login', [
        'email' => ' STAFF@RENTABIKE.COM ',
        'password' => 'secret-password',
    ]);

    $response->assertRedirect('/admin/dashboard');
    $this->assertAuthenticated();
});
