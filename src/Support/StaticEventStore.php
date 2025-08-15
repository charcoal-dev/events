<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Support;

use Charcoal\Events\AbstractEvent;
use Charcoal\Events\Contracts\EventStoreOwnerInterface;

/**
 * Class StaticEventBook
 * @package Charcoal\Events\Support
 */
class StaticEventStore
{
    private static array $events = [];

    /**
     * @template T of AbstractEvent
     * @param T $event
     * @param EventStoreOwnerInterface $store
     * @param bool $replace
     */
    public static function registerEvent(
        AbstractEvent            $event,
        EventStoreOwnerInterface $store,
        bool                     $replace = false
    ): void
    {
        $classname = static::normalizeEventClassname($event::class);
        $contextKey = static::normalizeContextKey($store->eventsUniqueContextKey());
        if (!$replace && isset(static::$events[$classname][$contextKey])) {
            throw new \LogicException("Event $classname for this context already registered");
        }

        static::$events[$classname][$contextKey] = $event;
    }

    /**
     * @template T of AbstractEvent
     * @param class-string<T> $eventClass
     * @param EventStoreOwnerInterface $store
     * @return T
     */
    public static function getEvent(string $eventClass, EventStoreOwnerInterface $store): AbstractEvent
    {
        $classname = static::normalizeEventClassname($eventClass);
        $contextKey = static::normalizeContextKey($store->eventsUniqueContextKey());
        if (!isset(static::$events[$classname][$contextKey])) {
            throw new \LogicException("Event $classname for this context not registered");
        }
        return static::$events[$classname][$contextKey];
    }

    /**
     * @template T of AbstractEvent
     * @param class-string<T> $eventClass
     * @param EventStoreOwnerInterface|null $store
     * @return void
     */
    public static function unregisterEvent(string $eventClass, ?EventStoreOwnerInterface $store): void
    {
        if (!$eventClass) {
            throw new \LogicException("Event classname must be provided");
        }

        $classname = static::normalizeEventClassname($eventClass);
        if (isset(static::$events[$classname])) {
            if (!$store) {
                unset(static::$events[$classname]);
                return;
            }

            $contextKey = static::normalizeContextKey($store->eventsUniqueContextKey());
            unset(static::$events[$classname][$contextKey]);
        }
    }

    /**
     * @return array
     */
    public static function inspect(): array
    {
        return array_map(
            fn(array $contexts) => array_keys($contexts),
            static::$events
        );
    }

    /**
     * @param class-string $classname
     * @return string
     */
    protected static function normalizeEventClassname(string $classname): string
    {
        return ltrim($classname, '\\');
    }

    /**
     * @param string $key
     * @return string
     */
    protected static function normalizeContextKey(string $key): string
    {
        return $key;
    }
}