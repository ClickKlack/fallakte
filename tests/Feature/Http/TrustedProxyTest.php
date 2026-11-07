<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/_proxy-check', fn (Request $request) => [
        'secure' => $request->isSecure(),
        'root' => $request->root(),
    ]);
});

/**
 * Simuliert eine Anfrage, die ein Reverse-Proxy per HTTPS entgegengenommen und weitergereicht hat.
 */
function forwardedRequest(string $proxyIp)
{
    return test()
        ->withServerVariables(['REMOTE_ADDR' => $proxyIp])
        ->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'fallakte.example.org',
            'X-Forwarded-Port' => '443',
        ])
        ->get('/_proxy-check');
}

test('forwarded https from a trusted proxy is honoured', function () {
    config(['trustedproxy.proxies' => '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16']);

    forwardedRequest('172.17.0.1')->assertExactJson([
        'secure' => true,
        'root' => 'https://fallakte.example.org',
    ]);
});

test('forwarded headers from an untrusted address are ignored', function () {
    config(['trustedproxy.proxies' => '172.16.0.0/12']);

    forwardedRequest('203.0.113.5')->assertJsonPath('secure', false);
});

test('no proxy is trusted without configuration', function () {
    config(['trustedproxy.proxies' => null]);

    forwardedRequest('172.17.0.1')->assertJsonPath('secure', false);
});
