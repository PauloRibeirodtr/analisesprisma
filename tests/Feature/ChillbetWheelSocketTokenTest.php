<?php

use App\Models\User;

beforeEach(function () {
    config([
        'chillbet.ws_url' => 'https://socket.chillbet.test',
        'chillbet.ws_jwt_secret' => 'test-jwt-secret',
        'chillbet.ws_token_ttl' => 1200,
        'chillbet.ws_origin' => 'analisesprisma',
    ]);

    $this->verifiedUser = new User([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'perfil' => 'administrador',
    ]);
    $this->verifiedUser->forceFill([
        'id' => 42,
        'email_verified_at' => now(),
    ]);
});

it('requires authentication to issue a ChillBet socket token', function () {
    $this->getJson(route('chillbet.wheel.socket-token'))
        ->assertUnauthorized();
});

it('issues a signed short-lived token for the authenticated user', function () {
    $response = $this->actingAs($this->verifiedUser)
        ->getJson(route('chillbet.wheel.socket-token'))
        ->assertSuccessful()
        ->assertJsonPath('ws_url', 'https://socket.chillbet.test')
        ->assertJsonPath('ttl', 1200)
        ->assertJsonStructure([
            'ws_url',
            'token',
            'expires_at',
            'ttl',
        ]);

    $token = $response->json('token');
    $segments = explode('.', $token);

    expect($segments)->toHaveCount(3);

    $payload = json_decode(base64_decode(strtr($segments[1], '-_', '+/')), true, flags: JSON_THROW_ON_ERROR);
    $expectedSignature = rtrim(strtr(base64_encode(
        hash_hmac('sha256', $segments[0].'.'.$segments[1], 'test-jwt-secret', true)
    ), '+/', '-_'), '=');

    expect($segments[2])->toBe($expectedSignature)
        ->and($payload['user_id'])->toBe(42)
        ->and($payload['origem'])->toBe('analisesprisma')
        ->and($payload['exp'] - $payload['iat'])->toBe(1200)
        ->and($response->json('expires_at'))->toBe($payload['exp']);
});

it('fails safely when the socket secret is not configured', function () {
    config(['chillbet.ws_jwt_secret' => null]);

    $this->actingAs($this->verifiedUser)
        ->getJson(route('chillbet.wheel.socket-token'))
        ->assertInternalServerError()
        ->assertJsonMissing(['token']);
});
