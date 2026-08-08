<?php

declare(strict_types=1);

namespace Capell\Smart404\Http\Controllers;

use Capell\Core\Models\SiteDomain;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Smart404\Actions\ResolveSmart404SuggestionsAction;
use Capell\Smart404\Data\Smart404SuggestionData;
use Capell\Smart404\Settings\Smart404Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

final class Smart404SuggestionsController
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->enabled()) {
            abort(404);
        }

        $path = $request->query('path');
        if (! is_string($path) || $this->malformedPath($path)) {
            return response()->json(['message' => trans('capell-smart-404::generic.invalid_path')], 422);
        }

        $context = app()->bound(FrontendContextReader::class)
            ? resolve(FrontendContextReader::class)
            : null;
        $site = $context?->site();
        $language = $context?->language();

        if ($site === null || $language === null) {
            $domain = SiteDomain::query()
                ->with(['site', 'language'])
                ->where('domain', $request->getHost())
                ->where('status', true)
                ->first();
            $site = $domain?->site;
            $language = $domain?->language;
        }

        if ($site === null || $language === null) {
            return $this->suggestionsResponse(collect());
        }

        $suggestions = ResolveSmart404SuggestionsAction::run($path, $site, $language);

        return $this->suggestionsResponse($suggestions);
    }

    /** @param Collection<int, Smart404SuggestionData> $suggestions */
    private function suggestionsResponse(Collection $suggestions): JsonResponse
    {
        return response()
            ->json(['suggestions' => $suggestions->map(static fn ($suggestion): array => [
                'title' => $suggestion->title,
                'url' => $suggestion->url,
            ])->values()->all()])
            ->header('Cache-Control', 'private, max-age=60')
            ->header('Vary', 'Host, Accept-Language');
    }

    private function enabled(): bool
    {
        try {
            if (app()->bound(Smart404Settings::class)) {
                return resolve(Smart404Settings::class)->enabled === true;
            }
        } catch (Throwable) {
            // Fall back to config when settings have not been installed yet.
        }

        return config('capell-smart-404.enabled', true) === true;
    }

    private function malformedPath(string $path): bool
    {
        $decodedPath = rawurldecode($path);

        return trim($path) === ''
            || ! str_starts_with($path, '/')
            || str_contains($path, "\0")
            || preg_match('/[\\x00-\\x1F\\x7F]/', $path) === 1
            || preg_match('/[\\x00-\\x1F\\x7F]/', $decodedPath) === 1
            || parse_url($path) === false
            || isset(parse_url($path)['scheme'], parse_url($path)['host']);
    }
}
