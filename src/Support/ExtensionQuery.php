<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Management\Support;

use Simtabi\Laranail\Package\Management\Extension;

/**
 * Immutable, fluent filter over a discovered extension set. Each method returns a new
 * query, so chains never mutate shared state: `Extensions::query()->role('plugin')->active()->get()`.
 */
final readonly class ExtensionQuery
{
    /** @param  list<Extension>  $items */
    public function __construct(private array $items) {}

    public function role(string $role): self
    {
        return $this->filter(static fn (Extension $e): bool => $e->role === $role);
    }

    public function active(): self
    {
        return $this->filter(static fn (Extension $e): bool => $e->enabled);
    }

    public function inactive(): self
    {
        return $this->filter(static fn (Extension $e): bool => ! $e->enabled);
    }

    /** Extensions that declare $id in their `require` list. */
    public function requiring(string $id): self
    {
        return $this->filter(static fn (Extension $e): bool => in_array($id, $e->require, true));
    }

    /** @param  callable(Extension):bool  $predicate */
    public function where(callable $predicate): self
    {
        return $this->filter($predicate);
    }

    /** @return list<Extension> */
    public function get(): array
    {
        return $this->items;
    }

    public function first(): ?Extension
    {
        return $this->items[0] ?? null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /** @return list<string> */
    public function ids(): array
    {
        return array_map(static fn (Extension $e): string => $e->id, $this->items);
    }

    /** @param  callable(Extension):bool  $predicate */
    private function filter(callable $predicate): self
    {
        return new self(array_values(array_filter($this->items, $predicate)));
    }
}
