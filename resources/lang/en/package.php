<?php

declare(strict_types=1);

return [
    'installed' => 'Smart 404 installed successfully.',
    'health' => [
        'hook' => [
            'label' => 'Smart 404 frontend hook',
            'passed' => 'The Smart 404 widget is registered after frontend content.',
            'failed' => 'The Smart 404 frontend hook is not registered.',
            'remediation' => 'Ensure the Smart 404 package is installed and the frontend hook registrar is available.',
        ],
    ],
];
