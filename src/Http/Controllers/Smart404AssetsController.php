<?php

declare(strict_types=1);

namespace Capell\Smart404\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class Smart404AssetsController
{
    public function script(): BinaryFileResponse
    {
        return $this->asset('smart-404.js', 'application/javascript; charset=UTF-8');
    }

    public function styles(): BinaryFileResponse
    {
        return $this->asset('smart-404.css', 'text/css; charset=UTF-8');
    }

    private function asset(string $filename, string $contentType): BinaryFileResponse
    {
        $response = response()->file(dirname(__DIR__, 3) . '/resources/dist/' . $filename);
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Cache-Control', 'public, max-age=86400, immutable');

        return $response;
    }
}
