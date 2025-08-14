<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Dispatch;

use Charcoal\Base\Traits\NotSerializableTrait;
use Charcoal\Events\AbstractEvent;
use Charcoal\Events\Contracts\EventContextInterface;

/**
 * Class DispatchReport
 * @package Charcoal\Events\Event
 * @var array<string,SubscriberResult> $result
 */
readonly class DispatchReport
{
    use NotSerializableTrait;

    public function __construct(
        public AbstractEvent         $event,
        public EventContextInterface $context,
        public array                 $result,
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
            "event" => $this->event::class,
            "context" => [$this->context::class],
            "result" => $this->result,
        ];
    }
}