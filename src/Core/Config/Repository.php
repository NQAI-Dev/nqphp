<?php

declare(strict_types=1);

namespace Nqphp\Core\Config;

/**
 * In-memory dot-notation configuration repository.
 */
class Repository
{
    /**
     * @param array<string, mixed> $items
     */
    public function __construct(private array $items = [])
    {
    }

    /**
     * Determine if the given configuration value exists.
     */
    public function has(string $key): bool
    {
        if (array_key_exists($key, $this->items)) {
            return true;
        }

        $items = $this->items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($items) || !array_key_exists($segment, $items)) {
                return false;
            }
            $items = $items[$segment];
        }

        return true;
    }

    /**
     * Get the specified configuration value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        $items = $this->items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($items) || !array_key_exists($segment, $items)) {
                return $default;
            }
            $items = $items[$segment];
        }

        return $items;
    }

    /**
     * Set a given configuration value.
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $items = &$this->items;

        while (count($keys) > 1) {
            $k = array_shift($keys);
            if (!isset($items[$k]) || !is_array($items[$k])) {
                $items[$k] = [];
            }
            $items = &$items[$k];
        }

        $items[array_shift($keys)] = $value;
    }

    /**
     * Get all configuration items.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }
}
