<?php

declare(strict_types=1);

/*
 * Hello feature configuration.
 *
 * Demo values showing the patterns:
 *   - cache_ttl: how long to cache the response body (seconds)
 *   - rate_limit: requests-per-minute per IP for this feature
 *   - feature_flags: per-feature A/B switches (no DB lookup; loaded at boot)
 */

return [
    'cache_ttl' => 60,
    'rate_limit' => 100,
    'feature_flags' => [
        'show_emoji' => true,
        'compact_view' => false,
    ],
];
