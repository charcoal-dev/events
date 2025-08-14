<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Exception;

use Charcoal\Events\Subscriptions\Subscription;

/**
 * Class SubscriptionClosedException
 * @package Charcoal\Events\Exception
 */
class SubscriptionClosedException extends \Exception
{
    /**
     * @param Subscription $subscription
     */
    public function __construct(
        public readonly Subscription $subscription,
    )
    {
        parent::__construct("Subscription closed");
    }
}