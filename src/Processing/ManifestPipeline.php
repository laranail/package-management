<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Processing;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Simtabi\Laranail\Package\Management\Extension;

/**
 * Runs each discovered {@see Extension} through ordered, pluggable stages (normalize /
 * validate / enrich) before it reaches the loader. Stages arrive from three sources, in
 * this fixed order:
 *   1. config `laranail.package-management.pipeline.stages` (class-strings — cache-safe)
 *   2. container-tagged `laranail.manifest.stages`
 *   3. runtime `pipe()` (the manager's fluent DSL — class-strings or closures)
 *
 * A stage is any `handle(Extension $extension, Closure $next): Extension`.
 */
final class ManifestPipeline
{
    /** @var list<string|Closure> */
    private array $runtimeStages = [];

    public function __construct(private readonly Container $container) {}

    public function pipe(string|Closure $stage): self
    {
        $this->runtimeStages[] = $stage;

        return $this;
    }

    public function process(Extension $extension): Extension
    {
        $result = (new Pipeline($this->container))
            ->send($extension)
            ->through($this->stages())
            ->thenReturn();

        return $result instanceof Extension ? $result : $extension;
    }

    /** @return list<string|Closure> */
    private function stages(): array
    {
        /** @var list<string> $configured */
        $configured = (array) config('laranail.package-management.pipeline.stages', []);

        return [
            ...$configured,
            ...array_values(iterator_to_array($this->container->tagged('laranail.manifest.stages'))),
            ...$this->runtimeStages,
        ];
    }
}
