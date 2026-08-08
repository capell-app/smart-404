<?php

declare(strict_types=1);

namespace Capell\Smart404\Data;

final readonly class Smart404PublicUrlEntryData
{
    public function __construct(
        public string $canonicalUrl,
        public int|string|null $siteId,
        public int|string|null $languageId,
        public bool $isIndexable,
        public ?string $title,
    ) {}
}
