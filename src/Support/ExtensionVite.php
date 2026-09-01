<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Support;

use Illuminate\Foundation\Vite;
use Illuminate\Support\HtmlString;

/**
 * Renders an extension's Vite tags from ITS published build directory
 * (`public/vendor/{slug}/build`, put there by the PublishesAssets capability), using a
 * FRESH {@see Vite} instance per call so the app-global Vite configuration is never mutated.
 *
 * No-op (empty string) when Laravel's Foundation Vite isn't present — e.g. under the Lumen
 * or Symfony adapters — so the helper is always safe to call.
 */
final class ExtensionVite
{
    /**
     * @param  string|list<string>  $entrypoints
     */
    public function __invoke(string $id, string|array $entrypoints, ?string $buildDirectory = null): HtmlString
    {
        if (! class_exists(Vite::class)) {
            return new HtmlString('');
        }

        $slug = str_replace('/', '-', $id);
        $build = $buildDirectory ?? "vendor/{$slug}/build";

        $vite = new Vite;
        $vite->useHotFile(public_path("vendor/{$slug}/hot"))->useBuildDirectory($build);

        return $vite((array) $entrypoints);
    }
}
