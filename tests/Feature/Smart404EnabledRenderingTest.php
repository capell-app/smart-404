<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Events\FrontendRenderPreparing;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Frontend\Support\State\FrontendState;
use Capell\Smart404\Data\Smart404SuggestionData;
use Capell\Smart404\Settings\Smart404Settings;
use Capell\Smart404\Support\RenderHooks\RegisterSmart404Hook;
use Capell\Tests\Support\RenderedHtml;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

it('honours the enabled setting in the initial error response', function (bool $enabled, bool $configEnabled): void {
    config()->set('capell-smart-404.enabled', $configEnabled);
    Smart404Settings::fake(['enabled' => $enabled, 'max_suggestions' => 5]);

    Route::get('/smart404-setting-error', static function (): Response {
        $state = (new FrontendState)->markAsError();
        $state->setFrontendData('smart-404.suggestions', collect([
            new Smart404SuggestionData(title: 'Documentation', url: '/docs'),
        ]));
        Frontend::swap($state);

        $registry = new RenderHookRegistry;
        $registry->registerExtension(
            location: RenderHookLocation::AfterContent,
            extension: new RegisterSmart404Hook,
        );
        $output = $registry->renderAll(
            RenderHookLocation::AfterContent,
            scenario: 'frontend-main-layout',
            target: 'capell::layout.main',
        );

        return response()->make('<main><a href="/">Back to home</a>' . $output . '</main>', Response::HTTP_NOT_FOUND);
    });

    $response = $this->get('/smart404-setting-error');
    $response->assertNotFound()->assertElementExists('main a[href="/"]');
    $html = $response->getContent();
    assert(is_string($html));
    $dom = new RenderedHtml($html);
    $expected = $enabled ? 1 : 0;

    expect($dom->count('//*[@id="capell-smart-404"]'))->toBe($expected)
        ->and($dom->count('//script[@src="' . route('capell-smart-404.script', [], false) . '"]'))->toBe($expected)
        ->and($dom->count('//link[@href="' . route('capell-smart-404.styles', [], false) . '"]'))->toBe($expected)
        ->and($dom->count('//a[@href="/docs"]'))->toBe($expected);
})->with([
    'enabled' => [true, true],
    'disabled' => [false, true],
    'saved setting takes precedence' => [true, false],
]);

it('keeps the suggestions endpoint disabled through settings and config fallback', function (string $mode): void {
    config()->set('capell-smart-404.enabled', $mode === 'saved');
    if ($mode === 'saved') {
        Smart404Settings::fake(['enabled' => false, 'max_suggestions' => 5]);
    } elseif ($mode === 'uninstalled') {
        app()->offsetUnset(Smart404Settings::class);
    } else {
        app()->bind(Smart404Settings::class, static fn (): never => throw new RuntimeException('Settings unavailable.'));
    }

    $this->get('/smart-404/suggestions?path=%2Fmissing')->assertNotFound();
})->with(['saved', 'uninstalled', 'unavailable']);

it('prepares suggestions only while Smart404 is enabled', function (bool $enabled): void {
    Smart404Settings::fake(['enabled' => $enabled, 'max_suggestions' => 5]);
    $context = new FrontendState;

    Event::dispatch(new FrontendRenderPreparing(
        context: $context,
        renderContext: new FrontendRenderContextData(
            page: null,
            site: (new Site)->forceFill(['id' => 1]),
            language: (new Language)->forceFill(['id' => 1]),
            layout: null,
            theme: null,
            isError: true,
        ),
    ));

    if ($enabled) {
        expect($context->getFrontendData('smart-404.suggestions'))->toBeInstanceOf(Collection::class);
    } else {
        expect($context->getFrontendData('smart-404.suggestions'))->toBeNull();
    }
})->with(['enabled' => true, 'disabled' => false]);
