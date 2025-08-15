<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Support\Traits;

use Charcoal\Events\AbstractEvent;
use Charcoal\Events\Contracts\EventStoreOwnerInterface;
use Charcoal\Events\Support\StaticEventStore;

/**
 * @template T of AbstractEvent
 * @template S of EventStoreOwnerInterface
 * @mixin AbstractEvent
 */
trait EventStaticScopeTrait
{
    /**
     * @param S $owner
     * @param bool $replace
     * @return void
     */
    public function registerStaticEventStore(EventStoreOwnerInterface $owner, bool $replace = false): void
    {
        StaticEventStore::registerEvent($this, $owner, $replace);
    }

    /**
     * @param S $owner
     * @return T
     */
    public static function getEvent(EventStoreOwnerInterface $owner): AbstractEvent
    {
        return StaticEventStore::getEvent(static::class, $owner);
    }
}