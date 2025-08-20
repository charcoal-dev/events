<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Stats;

use Charcoal\Events\Contracts\EventContextInterface;

/**
 * This class provides basic properties to track the number of events emitted,
 * connections, and the highest recorded value for a specific metric.
 */
final class EventStats
{
    /** @var array<class-string<EventContextInterface>,int> Total emissions per context */
    public array $emits;
    /** @var array<class-string<EventContextInterface>,array<string>> Current listeners attached */
    public array $current;
    /** @var array<class-string<EventContextInterface>,array<string>> Subscriber history */
    public array $history;
}