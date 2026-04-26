<?php

declare(strict_types=1);

namespace App\Application\Communication\Services;

use InvalidArgumentException;

/**
 * Driver-pattern registry: looks up a {@see NotificationChannel} by name.
 *
 * Sprint 13.4 registers 9 placeholder channels behind the same Log adapter.
 * Sprint 16 will register one real adapter per channel name.
 */
final class NotificationChannelRegistry
{
    /** @var array<string, NotificationChannel> */
    private array $channels = [];

    public function register(NotificationChannel $channel): void
    {
        $this->channels[$channel->name()] = $channel;
    }

    public function get(string $name): NotificationChannel
    {
        if (! isset($this->channels[$name])) {
            throw new InvalidArgumentException(sprintf('Notification channel "%s" is not registered', $name));
        }

        return $this->channels[$name];
    }

    /** @return array<int, string> */
    public function names(): array
    {
        return array_keys($this->channels);
    }
}
