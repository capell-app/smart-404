<?php

declare(strict_types=1);

namespace Capell\Smart404\Support\RenderHooks;

use Capell\Frontend\Contracts\RenderHookExtensionInterface;
use Capell\Frontend\Data\RenderHookContext;
use Capell\Frontend\Facades\Frontend;
use Capell\Smart404\Data\Smart404SuggestionData;

final class RegisterSmart404Hook implements RenderHookExtensionInterface
{
    public function render(RenderHookContext $context): string
    {
        if (! Frontend::isError()) {
            return '';
        }

        $suggestions = Frontend::getFrontendData('smart-404.suggestions');

        return view('capell-smart-404::widget', [
            'suggestions' => is_iterable($suggestions)
                ? collect($suggestions)->filter(fn (mixed $suggestion): bool => $suggestion instanceof Smart404SuggestionData)->values()
                : collect(),
        ])->render();
    }
}
