<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Dispatch;

use Charcoal\Base\Objects\Traits\NotSerializableTrait;
use Charcoal\Events\AbstractEvent;
use Charcoal\Events\Contracts\EventContextInterface;

/**
 * This class is immutable and provides detailed information about the event that was dispatched,
 * the context in which it was executed, the results produced by the listeners,
 * and the count of listeners involved in handling the event.
 * @var array<string,SubscriberResult> $result
 */
readonly class DispatchReport
{
    use NotSerializableTrait;

    public function __construct(
        public AbstractEvent         $event,
        public EventContextInterface $context,
        public array                 $result,
        public int                   $listenerCount
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
            "listenerCount" => $this->listenerCount,
            "result" => $this->result,
        ];
    }
}