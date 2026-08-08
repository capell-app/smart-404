<?php

declare(strict_types=1);

namespace Capell\Smart404\Support\Assets;

use Illuminate\Filesystem\Filesystem;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/** @method static void run(?string $publicRoot = null) */
final class PublishSmart404AssetsAction
{
    use AsFake;
    use AsObject;

    public function __construct(private readonly Filesystem $filesystem) {}

    public function handle(?string $publicRoot = null): void
    {
        $publicRoot ??= public_path();
        $sourceDirectory = dirname(__DIR__, 3) . '/resources/dist';
        $destinationDirectory = $publicRoot . '/vendor/capell-smart-404';
        $this->filesystem->ensureDirectoryExists($destinationDirectory);

        foreach (['smart-404.js', 'smart-404.css'] as $asset) {
            throw_unless($this->filesystem->isFile($sourceDirectory . '/' . $asset), RuntimeException::class, 'Smart 404 asset is missing: ' . $asset);
            throw_unless($this->filesystem->copy($sourceDirectory . '/' . $asset, $destinationDirectory . '/' . $asset), RuntimeException::class, 'Unable to publish Smart 404 asset: ' . $asset);
        }
    }
}
