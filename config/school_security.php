<?php

return [
    // Production closes every legacy global administration route by default.
    // Local and automated test environments preserve older development screens
    // until workflows are migrated. Never enable in a multi-school deployment.
    'legacy_global_routes_enabled' => (bool) env(
        'LEGACY_GLOBAL_ROUTES_ENABLED',
        app()->environment(['local', 'testing'])
    ),
];
