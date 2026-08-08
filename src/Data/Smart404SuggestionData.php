<?php

declare(strict_types=1);

namespace Capell\Smart404\Data;

use Spatie\LaravelData\Data;

final class Smart404SuggestionData extends Data
{
    public function __construct(
        public readonly string $title,
        public readonly string $url,
    ) {}
}
