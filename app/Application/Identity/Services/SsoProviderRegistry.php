<?php

declare(strict_types=1);

namespace App\Application\Identity\Services;

use InvalidArgumentException;

/**
 * Driver-pattern registry: looks up an {@see SsoProvider} by name.
 *
 * Sprint 13.2 ships ONE provider behind every name (the placeholder).
 * Sprint 16 will register one Socialite-backed instance per provider name.
 * Either way, controllers depend on this registry, never on a concrete driver.
 */
final class SsoProviderRegistry
{
    /** @var array<string, SsoProvider> */
    private array $providers = [];

    public function register(SsoProvider $provider): void
    {
        $this->providers[$provider->name()] = $provider;
    }

    public function get(string $name): SsoProvider
    {
        if (! isset($this->providers[$name])) {
            throw new InvalidArgumentException(sprintf('SSO provider "%s" is not registered', $name));
        }

        return $this->providers[$name];
    }

    /** @return array<int, string> */
    public function names(): array
    {
        return array_keys($this->providers);
    }
}
