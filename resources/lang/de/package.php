<?php

declare(strict_types=1);

return [
    'installed' => 'Smart 404 wurde erfolgreich installiert.',
    'health' => [
        'hook' => [
            'label' => 'Smart 404: Behandlung fehlender Seiten registriert',
            'passed' => 'Das Smart-404-Widget ist nach dem Frontend-Inhalt registriert.',
            'failed' => 'Der Smart-404-Frontend-Hook ist nicht registriert.',
            'remediation' => 'Stellen Sie sicher, dass das Paket Smart 404 installiert und der Registrar für den Frontend-Hook verfügbar ist.',
        ],
    ],
];
