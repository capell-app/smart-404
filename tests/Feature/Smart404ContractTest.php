<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\DiscoveryFoundation\Data\PublicUrlRegistryEntryData as FoundationEntry;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Frontend\Support\State\FrontendState;
use Capell\SiteDiscovery\Data\PublicUrlRegistryEntryData as LegacyEntry;
use Capell\SiteDiscovery\Enums\PublicUrlContentType as LegacyContentType;
use Capell\SiteDiscovery\Enums\PublicUrlIndexability as LegacyIndexability;
use Capell\Smart404\Actions\ResolveSmart404SuggestionsAction;
use Capell\Smart404\Support\RenderHooks\RegisterSmart404Hook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Livewire\Blaze\Blaze;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    $this->site = (new Site)->forceFill(['id' => 1]);
    $this->language = (new Language)->forceFill(['id' => 1]);
    app()->instance('request', Request::create('https://example.test/missing'));
    config()->set('capell-smart-404.minimum_similarity', 0.55);
    config()->set('capell-smart-404.max_candidates', 250);
});

it('ranks similar public URLs before hierarchy fallback', function (): void {
    $entries = collect([
        new FoundationEntry(
            canonicalUrl: 'https://example.test/docs',
            sourcePackage: 'capell-app/test',
            siteKey: 1,
            languageKey: 1,
            siteId: 1,
            languageId: 1,
            title: 'Documentation',
        ),
        new FoundationEntry(
            canonicalUrl: 'https://example.test/docs/laravel',
            sourcePackage: 'capell-app/test',
            siteKey: 1,
            languageKey: 1,
            siteId: 1,
            languageId: 1,
            title: 'Laravel',
        ),
        new FoundationEntry(
            canonicalUrl: 'https://example.test/docs/laravel/configuration',
            sourcePackage: 'capell-app/test',
            siteKey: 1,
            languageKey: 1,
            siteId: 1,
            languageId: 1,
            title: 'Configuration',
        ),
    ]);

    $suggestions = (new ResolveSmart404SuggestionsAction)->handle(
        path: '/docs/larave',
        site: $this->site,
        language: $this->language,
        entries: $entries,
    );

    expect($suggestions->firstOrFail()->url)->toBe('/docs/laravel')
        ->and($suggestions->pluck('url')->all())->toContain('/docs');
});

it('adapts actual legacy registry entries and excludes noindex or foreign scope data', function (): void {
    $entries = collect([
        new LegacyEntry(
            canonicalUrl: 'https://example.test/guides/laravel',
            sourcePackage: 'capell-app/site-discovery',
            siteKey: 1,
            languageKey: 1,
            siteId: 1,
            languageId: 1,
            indexability: LegacyIndexability::Indexable,
            contentType: LegacyContentType::Page,
            title: 'Laravel guide',
        ),
        new LegacyEntry(
            canonicalUrl: 'https://example.test/private',
            sourcePackage: 'capell-app/site-discovery',
            siteKey: 1,
            languageKey: 1,
            siteId: 1,
            languageId: 1,
            indexability: LegacyIndexability::NoIndex,
            contentType: LegacyContentType::Page,
            title: 'Private',
        ),
        new FoundationEntry(
            canonicalUrl: 'https://other.example.test/guides/laravel',
            sourcePackage: 'capell-app/test',
            siteKey: 1,
            languageKey: 1,
            siteId: 1,
            languageId: 1,
            title: 'Foreign host',
        ),
        new FoundationEntry(
            canonicalUrl: 'https://example.test/other-site',
            sourcePackage: 'capell-app/test',
            siteKey: 2,
            languageKey: 1,
            siteId: 2,
            languageId: 1,
            title: 'Other site',
        ),
        new FoundationEntry(
            canonicalUrl: 'https://example.test/fr',
            sourcePackage: 'capell-app/test',
            siteKey: 1,
            languageKey: 2,
            siteId: 1,
            languageId: 2,
            title: 'French',
        ),
    ]);
    config()->set('capell-smart-404.minimum_similarity', 0.0);

    $suggestions = (new ResolveSmart404SuggestionsAction)->handle(
        path: '/missing',
        site: $this->site,
        language: $this->language,
        entries: $entries,
    );

    expect($entries->first())->toBeInstanceOf(LegacyEntry::class)
        ->and($suggestions->pluck('url')->all())->toBe(['/guides/laravel'])
        ->and($suggestions->firstOrFail()->title)->toBe('Laravel guide');
});

it('uses a deterministic lexical tie-break and bounds path and candidate work', function (): void {
    $entries = collect([
        new FoundationEntry('https://example.test/foo/beta', 'capell-app/test', 1, 1, 1, 1, title: 'Same'),
        new FoundationEntry('https://example.test/foo/alpha', 'capell-app/test', 1, 1, 1, 1, title: 'Same'),
    ]);
    config()->set('capell-smart-404.minimum_similarity', 0.0);
    config()->set('capell-smart-404.max_candidates', 1);

    $suggestions = (new ResolveSmart404SuggestionsAction)->handle(
        path: '/foo/gamma',
        site: $this->site,
        language: $this->language,
        entries: $entries,
    );

    expect($suggestions->pluck('url')->all())->toBe(['/foo/alpha'])
        ->and((new ResolveSmart404SuggestionsAction)->handle(
            path: '/' . str_repeat('x', 2048),
            site: $this->site,
            language: $this->language,
            entries: $entries,
        ))->toBeEmpty();
});

it('returns hierarchy suggestions when similarity does not meet the threshold', function (): void {
    config()->set('capell-smart-404.minimum_similarity', 1.0);

    $suggestions = (new ResolveSmart404SuggestionsAction)->handle(
        path: '/docs/laravel/missing',
        site: $this->site,
        language: $this->language,
        entries: collect([
            new FoundationEntry('https://example.test/docs', 'capell-app/test', 1, 1, 1, 1, title: 'Docs'),
            new FoundationEntry('https://example.test/docs/laravel', 'capell-app/test', 1, 1, 1, 1, title: 'Laravel'),
            new FoundationEntry('https://example.test/docs/laravel/configuration', 'capell-app/test', 1, 1, 1, 1, title: 'Configuration'),
            new FoundationEntry('https://example.test/docs/laravel/install', 'capell-app/test', 1, 1, 1, 1, title: 'Install'),
        ]),
    );

    expect($suggestions->pluck('url')->all())->toBe([
        '/docs/laravel',
        '/docs/laravel/configuration',
        '/docs/laravel/install',
    ]);
});

it('fails closed for unknown hosts and scopes endpoint results to the resolved site and language', function (): void {
    $unknownHost = $this->withHeader('Host', 'unknown.example.test')
        ->get('/smart-404/suggestions?path=%2Fmissing');
    $unknownHost->assertOk()->assertExactJson(['suggestions' => []]);

    $site = (new Site)->forceFill(['id' => 2]);
    $language = (new Language)->forceFill(['id' => 2]);
    $context = Mockery::mock(FrontendContextReader::class);
    $context->shouldReceive('site')->andReturn($site);
    $context->shouldReceive('language')->andReturn($language);
    app()->instance(FrontendContextReader::class, $context);

    $response = $this->withHeader('Host', 'example.test')
        ->get('/smart-404/suggestions?path=%2Fmissing');

    $response->assertOk()
        ->assertHeader('Cache-Control', 'max-age=60, private')
        ->assertHeader('Vary', 'Host, Accept-Language')
        ->assertJsonPath('suggestions', []);
});

it('declares a bounded public rate limit and preserves an error response status around the hook', function (): void {
    $route = Route::getRoutes()->getByName('capell-smart-404.suggestions');
    $request = Request::create('https://example.test/smart-404/suggestions');
    $limiter = RateLimiter::limiter('capell-smart-404-suggestions');
    if (! is_callable($limiter)) {
        throw new RuntimeException('Smart 404 rate limiter is not registered.');
    }

    $limit = $limiter($request);

    expect($route)->not->toBeNull()
        ->and($route?->gatherMiddleware())->toContain('throttle:capell-smart-404-suggestions')
        ->and($limit->maxAttempts)->toBe(60)
        ->and($limit->decaySeconds)->toBe(60);

    Frontend::swap((new FrontendState)->markAsError());
    expect(Frontend::isError())->toBeTrue();
    $registry = new RenderHookRegistry;
    $registry->registerExtension(
        location: RenderHookLocation::AfterContent,
        extension: new RegisterSmart404Hook,
    );

    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();

    try {
        $output = $registry->renderAll(
            RenderHookLocation::AfterContent,
            scenario: 'frontend-main-layout',
            target: 'capell::layout.main',
        );
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }
    $response = response()->make('<main>' . $output . '</main>', Response::HTTP_NOT_FOUND);

    expect($response->getStatusCode())->toBe(Response::HTTP_NOT_FOUND)
        ->and($output)
        ->toContain('data-endpoint="/smart-404/suggestions"')
        ->toContain(route('capell-smart-404.script', [], false))
        ->toContain(route('capell-smart-404.styles', [], false))
        ->not->toContain('capell-app/smart-404')
        ->not->toContain('Filament')
        ->not->toContain('signed')
        ->and(Frontend::getFrontendData('smart-404.suggestions'))->toBeNull();
});
