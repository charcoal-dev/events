<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Contracts;

/**
 * Interface EventStoreOwnerInterface
 * @package Charcoal\Events\Contracts
 */
interface EventStoreOwnerInterface
{
    public function eventsUniqueContextKey(): string;
}