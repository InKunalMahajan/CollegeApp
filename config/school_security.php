<?php

return [
    // Configuration files are loaded before Laravel's environment binding
    // is fully initialized. Avoid app()->environment() here.
    // Strict mode is the default outside local and testing.
    'legacy_global_routes_enabled' => (bool) env(
        'LEGACY_GLOBAL_ROUTES_ENABLED',
        in_array(env('APP_ENV', 'production'), ['local', 'testing'], true)
    ),
];
