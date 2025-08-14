<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Tests\Fixture\NetworkStatus;

use Charcoal\Events\AbstractEvent;
use Charcoal\Events\Subscriptions\Subscription;

/**
 * Class NetworkStatusEvent
 * @package Charcoal\Events\Tests\Fixture
 */
class NetworkStatusEvent extends AbstractEvent
{
    public function subscribe(): Subscription
    {
        return $this->createSubscription("network-status-event-" .
            count($this->subscribers()) . "-" . uniqid());
    }
}