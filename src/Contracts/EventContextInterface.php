<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Contracts;

use Charcoal\Events\AbstractEvent;

/**
 * Represents a contract for an event context, defining the structure
 * and behavior that must be implemented by any class adhering to this interface.
 */
interface EventContextInterface
{
    /**
     * @return AbstractEvent
     */
    public function getEvent(): AbstractEvent;
}