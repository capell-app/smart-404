<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\DiscoveryFoundation\Data\PublicUrlRegistryEntryData;
use Capell\DiscoveryFoundation\Enums\PublicUrlContentType;
use Capell\DiscoveryFoundation\Enums\PublicUrlIndexability;
use Capell\Smart404\Actions\ResolveSmart404SuggestionsAction;
use Illuminate\Http\Request;

beforeEach(function (): void {
    app()->instance('request', Request::create('https://example.test/catalog/missing', 'GET'));
    config([
        'capell-smart-404.max_suggestions' => 5,
        'capell-smart-404.minimum_similarity' => 0.55,
    ]);
    $this->site = (new Site)->forceFill(['id' => 1]);
    $this->language = (new Language)->forceFill(['id' => 1, 'code' => 'en']);
});

function smart404Entry(string $url, string $title, int $siteId = 1, int $languageId = 1, PublicUrlIndexability $indexability = PublicUrlIndexability::Indexable): PublicUrlRegistryEntryData
{
    return new PublicUrlRegistryEntryData(
        canonicalUrl: 'https://example.test' . $url,
        sourcePackage: 'capell-app/test',
        siteKey: $siteId,
        languageKey: $languageId,
        siteId: $siteId,
        languageId: $languageId,
        indexability: $indexability,
        contentType: PublicUrlContentType::Page,
        title: $title,
    );
}

it('ranks similarity matches before scoped hierarchy fallbacks', function (): void {
    $suggestions = ResolveSmart404SuggestionsAction::run(
        '/catalog/missing',
        $this->site,
        $this->language,
        collect([
            smart404Entry('/catalog', 'Catalog'),
            smart404Entry('/catalog/misspelled', 'Misspelled'),
            smart404Entry('/catalog/featured', 'Featured'),
            smart404Entry('/pricing', 'Pricing'),
            smart404Entry('/other-site', 'Other', 2),
            smart404Entry('/hidden', 'Hidden', 1, 1, PublicUrlIndexability::NoIndex),
        ]),
    );

    expect($suggestions->pluck('url')->all())->toContain('/catalog/misspelled')
        ->and($suggestions->pluck('url')->all())->not->toContain('/pricing')
        ->and($suggestions->pluck('url')->all())->not->toContain('/hidden')
        ->and($suggestions->pluck('url')->all())->not->toContain('/catalog/missing')
        ->and($suggestions->count())->toBeLessThanOrEqual(5);
});

it('returns the deepest ancestor and direct children when no similarity qualifies', function (): void {
    config(['capell-smart-404.minimum_similarity' => 1.0]);

    $suggestions = ResolveSmart404SuggestionsAction::run(
        '/catalog/missing',
        $this->site,
        $this->language,
        collect([
            smart404Entry('/catalog', 'Catalog'),
            smart404Entry('/catalog/featured', 'Featured'),
            smart404Entry('/catalog/contact', 'Contact'),
            smart404Entry('/catalog/featured/details', 'Too deep'),
        ]),
    );

    expect($suggestions->pluck('url')->all())->toBe(['/catalog', '/catalog/contact', '/catalog/featured']);
});
