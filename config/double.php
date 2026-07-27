<?php

return [
    'crash_api_url' => env('CRASH_WINDOW_API_URL'),
    'crash_api_token' => env('CRASH_WINDOW_API_TOKEN'),
    'crash_api_timeout' => (int) env('CRASH_WINDOW_API_TIMEOUT', 8),
    'crash_api_connect_timeout' => (int) env('CRASH_WINDOW_API_CONNECT_TIMEOUT', 3),
    'crash_api_future_limit' => (int) env('CRASH_WINDOW_API_FUTURE_LIMIT', 4),
    'crash_api_previous_limit' => (int) env('CRASH_WINDOW_API_PREVIOUS_LIMIT', 100),
    'historico_timezone' => env('DOUBLE_HISTORY_TIMEZONE', 'America/Sao_Paulo'),
    'finalizacao_grace_seconds' => (int) env('DOUBLE_WINDOW_FINALIZATION_GRACE_SECONDS', 15),
    'tiros_estimados_por_minuto' => (int) env('DOUBLE_WINDOW_ESTIMATED_SHOTS_PER_MINUTE', 2),
    'painel_proximas' => (int) env('DOUBLE_WINDOW_UPCOMING_COUNT', 3),
    'painel_anteriores' => (int) env('DOUBLE_WINDOW_PREVIOUS_COUNT', 5),
    'ws_url' => env('DOUBLE_WS_URL', 'https://gestoranalista.gestorbet.com.br'),
    'ws_path' => env('DOUBLE_WS_PATH', '/double/socket.io'),
    'ws_jwt_secret' => env('DOUBLE_WS_JWT_SECRET'),
    'ws_token_ttl' => (int) env('DOUBLE_WS_TOKEN_TTL', 1200),
    'ws_origin' => env('DOUBLE_WS_TOKEN_ORIGIN', 'analisesprisma'),
];
