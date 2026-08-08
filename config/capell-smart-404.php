<?php

declare(strict_types=1);

return [
    'enabled' => true,
    'max_suggestions' => 5,
    'max_candidates' => 250,
    'minimum_similarity' => 0.55,
    'endpoint_path' => 'smart-404/suggestions',
    'client_timeout_ms' => 2000,
    'rate_limit' => ['per_minute' => 60],
];
