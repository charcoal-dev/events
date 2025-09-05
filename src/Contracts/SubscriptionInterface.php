<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Contracts;

use Charcoal\Events\AbstractEvent;

/**
 * Represents a subscription contract for managing event-based interactions,
 * allowing for the creation, delivery, and management of event listeners
 * and their associated contexts.
 */
interface SubscriptionInterface
{
    public function id(): string;

    public function status(): bool;

    /**
     * @return array<EventContextInterface>
     */
    public function listening(): array;

    /**
     * @param class-string<EventContextInterface> $context
     * @param \Closure $callback
     * @return bool
     */
    public function listen(string $context, \Closure $callback): bool;

    public function deliver(AbstractEvent $event, EventContextInterface $context): mixed;

    public function unsubscribe(): void;

    public function disconnect(AbstractEvent $event): void;
}
