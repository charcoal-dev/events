<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Tests\Fixture\NetworkStatus;

use Charcoal\Events\Contracts\EventContextInterface;

abstract readonly class BaseNetworkStatusEvent
    implements EventContextInterface
{
    public function __construct(
        protected NetworkStatusEvent $event
    )
    {
    }

    public function getEvent(): NetworkStatusEvent
    {
        return $this->event;
    }
}