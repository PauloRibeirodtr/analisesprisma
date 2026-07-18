<?php

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'chillbet.api_url' => 'https://chillbet.test',
        'chillbet.api_token' => 'test-api-token',
        'chillbet.default_limit' => 300,
        'chillbet.max_limit' => 1000,
    ]);

    Http::preventStrayRequests();

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

it('requires authentication to access the ChillBet history', function () {
    $this->getJson(route('api.chillbet.wheel.latest'))
        ->assertUnauthorized();
});

it('requires a verified email to access the ChillBet integration', function () {
    $this->verifiedUser->forceFill(['email_verified_at' => null]);

    $this->actingAs($this->verifiedUser)
        ->getJson(route('api.chillbet.wheel.latest'))
        ->assertForbidden();
});

it('validates the requested history limit', function () {
    $this->actingAs($this->verifiedUser)
        ->getJson(route('api.chillbet.wheel.latest', ['limit' => 1001]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('limit');
});

it('returns normalized ChillBet history through the protected proxy', function () {
    Http::fake([
        'https://chillbet.test/api/wheel/ultimos*' => Http::response([
            'total' => 3,
            'resultados' => [
                [
                    'id' => '10',
                    'gameId' => '20',
                    'cor' => 'blue',
                    'data' => '2026-07-14 10:00:00',
                ],
                [
                    'gameId' => 21,
                    'cor' => 'RED',
                    'data' => '2026-07-14 09:59:30',
                ],
                [
                    'gameId' => 0,
                    'cor' => 'INVALID',
                    'data' => '',
                ],
            ],
        ]),
    ]);

    $this->actingAs($this->verifiedUser)
        ->getJson(route('api.chillbet.wheel.latest', ['limit' => 1000]))
        ->assertSuccessful()
        ->assertExactJson([
            'tipo' => 'chillbet-wheel-historico',
            'limit' => 1000,
            'total' => 2,
            'resultados' => [
                [
                    'id' => 10,
                    'gameId' => 20,
                    'cor' => 'BLUE',
                    'data' => '2026-07-14 10:00:00',
                ],
                [
                    'id' => 21,
                    'gameId' => 21,
                    'cor' => 'RED',
                    'data' => '2026-07-14 09:59:30',
                ],
            ],
        ]);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://chillbet.test/api/wheel/ultimos?limit=1000'
        && $request->hasHeader('x-api-token', 'test-api-token'));
});

it('returns a bad gateway response when ChillBet rejects the API credentials', function () {
    Http::fake([
        'https://chillbet.test/*' => Http::response([], 401),
    ]);

    $this->actingAs($this->verifiedUser)
        ->getJson(route('api.chillbet.wheel.latest'))
        ->assertStatus(502)
        ->assertJsonPath('total', 0)
        ->assertJsonPath('resultados', []);
});

it('rejects an invalid response from the external API', function () {
    Http::fake([
        'https://chillbet.test/*' => Http::response(['resultados' => null]),
    ]);

    $this->actingAs($this->verifiedUser)
        ->getJson(route('api.chillbet.wheel.latest'))
        ->assertStatus(502)
        ->assertJsonPath('total', 0);
});

it('retries a transient server failure', function () {
    Http::fake([
        'https://chillbet.test/*' => Http::sequence()
            ->push([], 503)
            ->push([
                'total' => 1,
                'resultados' => [[
                    'id' => 10,
                    'gameId' => 20,
                    'cor' => 'GREEN',
                    'data' => '2026-07-14 10:00:00',
                ]],
            ]),
    ]);

    $this->actingAs($this->verifiedUser)
        ->getJson(route('api.chillbet.wheel.latest', ['limit' => 1]))
        ->assertSuccessful()
        ->assertJsonPath('total', 1);

    Http::assertSentCount(2);
});
