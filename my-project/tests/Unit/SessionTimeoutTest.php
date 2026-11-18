<?php

use App\Support\SessionTimeout;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;

function timeoutRequest(array $sessionData = []): Request
{
    $session = new Store('test', new ArraySessionHandler(120));
    $session->start();
    $session->replace($sessionData);

    $request = Request::create('/');
    $request->setLaravelSession($session);

    return $request;
}

test('session inactivity expires after fifteen minutes', function () {
    $request = timeoutRequest([
        SessionTimeout::KEY => time() - (15 * 60),
    ]);

    expect(SessionTimeout::hasExpired($request))->toBeTrue();
});

test('recent session activity keeps the session active', function () {
    $request = timeoutRequest([
        SessionTimeout::KEY => time() - 60,
    ]);

    expect(SessionTimeout::hasExpired($request))->toBeFalse();
});
