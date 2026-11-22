<?php

declare(strict_types=1);

namespace Capell\Smart404\Support\RenderHooks;

use Capell\Core\Contracts\Rendering\RenderHookExtensionInterface;
use Capell\Core\Data\Rendering\RenderHookContext;
use Capell\Frontend\Facades\Frontend;
use Capell\Smart404\Actions\IsSmart404EnabledAction;
use Capell\Smart404\Data\Smart404SuggestionData;
use Override;

final class RegisterSmart404Hook implements RenderHookExtensionInterface
{
    #[Override]
    public function render(RenderHookContext $context): string
    {
        if (! Frontend::isError() || ! IsSmart404EnabledAction::run()) {
            return '';
        }

        $suggestions = Frontend::getFrontendData('smart-404.suggestions');

        $timeout = config('capell-smart-404.client_timeout_ms', 2000);

        return view('capell-smart-404::widget', [
            'timeoutMs' => is_numeric($timeout) ? max(500, min(10000, (int) $timeout)) : 2000,
            'suggestions' => is_iterable($suggestions)
                ? collect($suggestions)->filter(fn (mixed $suggestion): bool => $suggestion instanceof Smart404SuggestionData)->values()
                : collect(),
        ])->render();
    }
}
