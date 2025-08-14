<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Contracts;

use Charcoal\Events\AbstractEvent;

/**
 * Interface SubscriptionInterface
 * @package Charcoal\Events\Contracts
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
     * @return void
     */
    public function listen(string $context, \Closure $callback): void;

    public function deliver(AbstractEvent $event, EventContextInterface $context): mixed;

    public function unsubscribe(): void;

    public function disconnect(AbstractEvent $event): void;
}
