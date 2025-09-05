<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Contracts;

/**
 * Interface for defining an owner of an event store.
 * Provides a method to retrieve a unique key representing the context of events.
 */
interface EventStoreOwnerInterface
{
    public function eventsUniqueContextKey(): string;
}