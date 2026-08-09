<?php

declare(strict_types=1);

namespace Capell\Smart404\Support\PublicUrls;

use BackedEnum;
use Capell\Core\Facades\CapellCore;
use Capell\DiscoveryFoundation\Data\PublicUrlRegistryEntryData as FoundationPublicUrlRegistryEntryData;
use Capell\DiscoveryFoundation\Enums\PublicUrlIndexability as FoundationPublicUrlIndexability;
use Capell\SiteDiscovery\Data\PublicUrlRegistryEntryData;
use Capell\Smart404\Data\Smart404PublicUrlEntryData;
use Illuminate\Support\Collection;

final class Smart404PublicUrlRegistryAdapter
{
    /**
     * @param  Collection<int, covariant mixed>  $entries
     * @return Collection<int, Smart404PublicUrlEntryData>
     */
    public function adapt(Collection $entries): Collection
    {
        return $entries
            ->map(fn (mixed $entry): ?Smart404PublicUrlEntryData => $this->adaptEntry($entry))
            ->filter()
            ->values();
    }

    private function adaptEntry(mixed $entry): ?Smart404PublicUrlEntryData
    {
        if ($entry instanceof FoundationPublicUrlRegistryEntryData) {
            return $this->fromFoundation($entry);
        }

        if (CapellCore::isPackageAvailable('capell-app/site-discovery')
            && $entry instanceof PublicUrlRegistryEntryData) {
            return $this->fromSiteDiscovery($entry);
        }

        return null;
    }

    private function fromFoundation(FoundationPublicUrlRegistryEntryData $entry): Smart404PublicUrlEntryData
    {
        return $this->fromValues(
            canonicalUrl: $entry->canonicalUrl,
            siteId: $entry->siteId,
            languageId: $entry->languageId,
            indexability: $entry->indexability,
            title: $entry->title,
        );
    }

    private function fromSiteDiscovery(object $entry): ?Smart404PublicUrlEntryData
    {
        $values = get_object_vars($entry);
        $canonicalUrl = $values['canonicalUrl'] ?? null;
        $title = $values['title'] ?? null;

        if (! is_string($canonicalUrl) || ($title !== null && ! is_string($title))) {
            return null;
        }

        return $this->fromValues(
            canonicalUrl: $canonicalUrl,
            siteId: $this->identifier($values['siteId'] ?? null),
            languageId: $this->identifier($values['languageId'] ?? null),
            indexability: $values['indexability'] ?? null,
            title: $title,
        );
    }

    private function identifier(mixed $value): int|string|null
    {
        return is_int($value) || is_string($value) ? $value : null;
    }

    private function fromValues(
        string $canonicalUrl,
        int|string|null $siteId,
        int|string|null $languageId,
        mixed $indexability,
        ?string $title,
    ): Smart404PublicUrlEntryData {
        return new Smart404PublicUrlEntryData(
            canonicalUrl: $canonicalUrl,
            siteId: $siteId,
            languageId: $languageId,
            isIndexable: $this->isIndexable($indexability),
            title: $title,
        );
    }

    private function isIndexable(mixed $indexability): bool
    {
        if ($indexability instanceof FoundationPublicUrlIndexability) {
            return $indexability === FoundationPublicUrlIndexability::Indexable;
        }

        if ($indexability instanceof BackedEnum) {
            return $indexability->value === FoundationPublicUrlIndexability::Indexable->value;
        }

        return $indexability === FoundationPublicUrlIndexability::Indexable->value;
    }
}
