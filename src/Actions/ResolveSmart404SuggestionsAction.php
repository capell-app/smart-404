<?php

declare(strict_types=1);

namespace Capell\Smart404\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\DiscoveryFoundation\Actions\BuildPublicUrlRegistryAction;
use Capell\DiscoveryFoundation\Actions\ScorePublicUrlCandidateAction;
use Capell\Smart404\Bridges\SiteDiscovery\Smart404PublicUrlRegistryAdapter;
use Capell\Smart404\Data\Smart404PublicUrlEntryData;
use Capell\Smart404\Data\Smart404SuggestionData;
use Capell\Smart404\Settings\Smart404Settings;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

/**
 * @method static Collection<int, Smart404SuggestionData> run(string $path, ?Site $site = null, ?Language $language = null, ?Collection<int, covariant mixed> $entries = null)
 */
final class ResolveSmart404SuggestionsAction
{
    use AsFake;
    use AsObject;

    private const int MaxMatchInputLength = 256;

    /**
     * @param  Collection<int, covariant mixed>|null  $entries
     * @return Collection<int, Smart404SuggestionData>
     */
    public function handle(
        string $path,
        ?Site $site = null,
        ?Language $language = null,
        ?Collection $entries = null,
    ): Collection {
        $requestedPath = $this->normalisePath($path);
        if ($requestedPath === null) {
            return collect();
        }

        /** @var Collection<int, array{entry: Smart404PublicUrlEntryData, url: string}> $registry */
        $registry = (new Smart404PublicUrlRegistryAdapter)->adapt($entries ?? BuildPublicUrlRegistryAction::run())
            ->filter(fn (Smart404PublicUrlEntryData $entry): bool => $entry->isIndexable)
            ->filter(fn (Smart404PublicUrlEntryData $entry): bool => $site === null || $this->sameIdentifier($entry->siteId, $this->modelIdentifier($site)))
            ->filter(fn (Smart404PublicUrlEntryData $entry): bool => $language === null || $this->sameIdentifier($entry->languageId, $this->modelIdentifier($language)))
            ->filter(fn (Smart404PublicUrlEntryData $entry): bool => $this->isCurrentOrigin($entry->canonicalUrl))
            ->map(fn (Smart404PublicUrlEntryData $entry): array => [
                'entry' => $entry,
                'url' => $this->relativeUrl($entry->canonicalUrl),
            ])
            ->filter(fn (array $candidate): bool => is_string($candidate['url']) && $candidate['url'] !== $requestedPath)
            ->sort(function (array $left, array $right): int {
                $urlComparison = strcmp((string) $left['url'], (string) $right['url']);

                if ($urlComparison !== 0) {
                    return $urlComparison;
                }

                /** @var Smart404PublicUrlEntryData $leftEntry */
                $leftEntry = $left['entry'];
                /** @var Smart404PublicUrlEntryData $rightEntry */
                $rightEntry = $right['entry'];

                return strcmp($leftEntry->title ?? '', $rightEntry->title ?? '');
            })
            ->unique(fn (array $candidate): string => (string) $candidate['url'])
            ->take($this->maxCandidates())
            ->values();

        if ($registry->isEmpty()) {
            return collect();
        }

        $missingTitle = Str::headline(str_replace(['-', '_'], ' ', basename(trim($requestedPath, '/'))));
        $similarityThreshold = $this->configFloat('capell-smart-404.minimum_similarity', 0.55);
        $scored = $registry
            ->map(function (array $candidate) use ($requestedPath, $missingTitle): array {
                /** @var Smart404PublicUrlEntryData $entry */
                $entry = $candidate['entry'];
                $url = (string) $candidate['url'];
                $title = $this->title($entry, $url);
                $score = ScorePublicUrlCandidateAction::run(
                    $this->boundMatchInput($requestedPath),
                    $this->boundMatchInput($missingTitle),
                    $this->boundMatchInput($url),
                    $this->boundMatchInput($title),
                );

                return [
                    ...$candidate,
                    'title' => $title,
                    'score' => $score->score,
                    'editSimilarity' => $score->editSimilarity,
                    'tokenDiceSimilarity' => $score->tokenDiceSimilarity,
                ];
            })
            ->filter(fn (array $candidate): bool => $candidate['score'] >= $similarityThreshold)
            ->sort(function (array $left, array $right): int {
                $scoreComparison = $right['score'] <=> $left['score'];

                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                $editComparison = $right['editSimilarity'] <=> $left['editSimilarity'];
                if ($editComparison !== 0) {
                    return $editComparison;
                }

                $tokenComparison = $right['tokenDiceSimilarity'] <=> $left['tokenDiceSimilarity'];

                return $tokenComparison !== 0
                    ? $tokenComparison
                    : strcmp((string) $left['url'], (string) $right['url']);
            })
            ->values();

        $limit = $this->maxSuggestions();
        $suggestions = $scored->take($limit)
            ->map(fn (array $candidate): Smart404SuggestionData => new Smart404SuggestionData(
                title: (string) $candidate['title'],
                url: (string) $candidate['url'],
            ));

        if ($suggestions->count() < $limit) {
            $suggestions = $suggestions
                ->concat($this->hierarchyFallback($registry, $requestedPath, $this->suggestionUrls($suggestions), $limit - $suggestions->count()))
                ->unique(fn (Smart404SuggestionData $suggestion): string => $suggestion->url)
                ->take($limit)
                ->values();
        }

        return $suggestions;
    }

    /**
     * @param  Collection<int, array{entry: Smart404PublicUrlEntryData, url: string}>  $registry
     * @param  list<string>  $excluded
     * @return Collection<int, Smart404SuggestionData>
     */
    private function hierarchyFallback(Collection $registry, string $requestedPath, array $excluded, int $remaining): Collection
    {
        if ($remaining < 1) {
            return collect();
        }

        $segments = array_values(array_filter(explode('/', trim($requestedPath, '/')), static fn (string $segment): bool => $segment !== ''));
        $ancestor = null;

        for ($length = count($segments) - 1; $length >= 0; $length--) {
            $candidatePath = '/' . implode('/', array_slice($segments, 0, $length));
            $candidatePath = $candidatePath === '/' ? '/' : rtrim($candidatePath, '/');

            if ($registry->contains(fn (array $candidate): bool => $candidate['url'] === $candidatePath)) {
                $ancestor = $candidatePath;

                break;
            }
        }

        $ancestor ??= '/';
        $ancestorDepth = $ancestor === '/' ? 0 : count(array_filter(explode('/', trim($ancestor, '/'))));
        $fallback = $registry
            ->filter(function (array $candidate) use ($ancestor, $ancestorDepth, $excluded): bool {
                $url = (string) $candidate['url'];
                if ($url === '/' || in_array($url, $excluded, true)) {
                    return false;
                }

                $depth = count(array_filter(explode('/', trim($url, '/'))));
                $prefix = $ancestor === '/' ? '/' : rtrim($ancestor, '/') . '/';

                return $depth === $ancestorDepth + 1 && str_starts_with($url, $prefix);
            })
            ->sortBy(fn (array $candidate): string => (string) $candidate['url'])
            ->map(fn (array $candidate): Smart404SuggestionData => new Smart404SuggestionData(
                title: $this->title($candidate['entry'], (string) $candidate['url']),
                url: (string) $candidate['url'],
            ));

        if ($ancestor !== '/' && ! in_array($ancestor, $excluded, true)) {
            $ancestorEntry = $registry->first(fn (array $candidate): bool => $candidate['url'] === $ancestor);
            if (is_array($ancestorEntry)) {
                $fallback = collect([new Smart404SuggestionData(
                    title: $this->title($ancestorEntry['entry'], $ancestor),
                    url: $ancestor,
                )])->concat($fallback);
            }
        }

        return $fallback->take($remaining)->values();
    }

    /**
     * @param  Collection<int, Smart404SuggestionData>  $suggestions
     * @return list<string>
     */
    private function suggestionUrls(Collection $suggestions): array
    {
        $urls = [];

        foreach ($suggestions as $suggestion) {
            if (! $suggestion instanceof Smart404SuggestionData) {
                continue;
            }

            $urls[] = $suggestion->url;
        }

        return $urls;
    }

    private function title(Smart404PublicUrlEntryData $entry, string $url): string
    {
        if (is_string($entry->title) && trim($entry->title) !== '') {
            return trim($entry->title);
        }

        $segment = basename(trim($url, '/'));

        return $segment === '' ? __('capell-smart-404::generic.home') : Str::headline(str_replace(['-', '_'], ' ', $segment));
    }

    private function relativeUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = '/';
        }

        return '/' . ltrim(rtrim(rawurldecode($path), '/'), '/');
    }

    private function isCurrentOrigin(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || ! app()->bound('request')) {
            return false;
        }

        $request = request();

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $port = parse_url($url, PHP_URL_PORT);
        $requestHost = $request->getHost();
        $requestPort = $request->getPort();

        return $requestHost !== ''
            && strtolower($host) === strtolower($requestHost)
            && is_string($scheme)
            && strtolower($scheme) === strtolower($request->getScheme())
            && ($port === null || (int) $port === $requestPort);
    }

    private function maxSuggestions(): int
    {
        try {
            if (app()->bound(Smart404Settings::class)) {
                $settings = resolve(Smart404Settings::class);

                return $this->clampInt($settings->max_suggestions, 5);
            }
        } catch (Throwable) {
            // Settings are optional during first install and package discovery.
        }

        return $this->clampInt(config('capell-smart-404.max_suggestions', 5), 5);
    }

    private function maxCandidates(): int
    {
        return $this->configInt('capell-smart-404.max_candidates', 250);
    }

    private function boundMatchInput(string $value): string
    {
        return mb_substr($value, 0, self::MaxMatchInputLength);
    }

    private function clampInt(mixed $value, int $default): int
    {
        return is_numeric($value) ? max(1, min(10, (int) $value)) : $default;
    }

    private function sameIdentifier(int|string|null $left, int|string|null $right): bool
    {
        return $left !== null && $right !== null && (string) $left === (string) $right;
    }

    private function modelIdentifier(Site|Language $model): int|string|null
    {
        $key = $model->getKey();

        return is_int($key) || is_string($key) ? $key : null;
    }

    private function normalisePath(string $path): ?string
    {
        $path = trim($path);

        if ($path === '' || strlen($path) > 2048 || ! str_starts_with($path, '/') || str_contains($path, "\0") || preg_match('/[\\x00-\\x1F\\x7F]/', $path) === 1) {
            return null;
        }

        $parsed = parse_url($path);
        if ($parsed === false || isset($parsed['scheme']) || isset($parsed['host'])) {
            return null;
        }

        $normalised = '/' . ltrim(rawurldecode((string) ($parsed['path'] ?? $path)), '/');

        if (preg_match('/[\x00-\x1F\x7F]/', $normalised) === 1) {
            return null;
        }

        return $normalised === '/' ? '/' : rtrim($normalised, '/');
    }

    private function configInt(string $key, int $default): int
    {
        $value = config($key, $default);

        return is_numeric($value) ? max(1, min(500, (int) $value)) : $default;
    }

    private function configFloat(string $key, float $default): float
    {
        $value = config($key, $default);

        return is_numeric($value) ? max(0.0, min(1.0, (float) $value)) : $default;
    }
}
