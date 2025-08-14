<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Dispatch;

use Charcoal\Base\Traits\NotSerializableTrait;
use Charcoal\Events\Subscriptions\Subscription;

/**
 * Class SubscriberResult
 * @package Charcoal\Events\Event
 */
readonly class SubscriberResult
{
    use NotSerializableTrait;

    public function __construct(
        public Subscription   $subscription,
        public ListenerResult $status,
        public mixed          $result = null,
        public ?\Throwable    $error = null,
    )
    {
    }

    public function __debugInfo(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            "subscription" => $this->subscription->id,
            "status" => $this->status->name,
            "result" => $this->result,
            "error" => $this->error ? [
                "exception" => get_class($this->error),
                "message" => $this->error->getMessage(),
                "code" => $this->error->getCode(),
            ] : null,
        ];
    }
}