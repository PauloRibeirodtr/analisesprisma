<?php

return [
    'api_url' => env('CHILLBET_API_URL', 'https://gestoranalista.gestorbet.com.br'),
    'api_token' => env('CHILLBET_API_TOKEN'),
    'timeout' => (int) env('CHILLBET_API_TIMEOUT', 8),
    'connect_timeout' => (int) env('CHILLBET_API_CONNECT_TIMEOUT', 3),
    'default_limit' => (int) env('CHILLBET_HISTORY_DEFAULT_LIMIT', 300),
    'max_limit' => (int) env('CHILLBET_HISTORY_MAX_LIMIT', 2000),
    'ws_url' => env('CHILLBET_WS_URL', env('CHILLBET_API_URL', 'https://gestoranalista.gestorbet.com.br')),
    'ws_jwt_secret' => env('CHILLBET_WS_JWT_SECRET'),
    'ws_token_ttl' => (int) env('CHILLBET_WS_TOKEN_TTL', 1200),
    'ws_origin' => env('CHILLBET_WS_ORIGIN', 'analisesprisma'),
];
