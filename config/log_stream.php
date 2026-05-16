<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Live log stream (WebSocket via nginx)
    |--------------------------------------------------------------------------
    |
    | Browser connects to the same host as the app (e.g. /ws/logs).
    | nginx proxies to the log-stream service and adds X-Log-Stream-Token.
    | Only authenticated users pass nginx auth_request (see routes/web.php).
    |
    */

    'enabled' => (bool) env('LOG_STREAM_ENABLED', true),

    /** WebSocket path on the app host (no token in the URL). */
    'ws_path' => env('LOG_STREAM_WS_PATH', '/ws/logs'),

    /** Max lines kept in the dashboard panel DOM. */
    'max_lines' => (int) env('LOG_STREAM_DASHBOARD_MAX_LINES', 400),

];
