<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Subscriptions;

use Charcoal\Base\Traits\NotCloneableTrait;
use Charcoal\Events\AbstractEvent;
use Charcoal\Events\Contracts\EventContextInterface;
use Charcoal\Events\Contracts\SubscriptionInterface;
use Charcoal\Events\Exceptions\ListenerErrorException;
use Charcoal\Events\Exceptions\SubscriberNotListeningException;
use Charcoal\Events\Exceptions\SubscriptionClosedException;

/**
 * Class Subscription
 * @package Charcoal\Events\Listener
 */
class Subscription implements SubscriptionInterface
{
    use NotCloneableTrait;

    /** @var array<class-string<EventContextInterface>,\Closure> */
    private array $listeners = [];
    private bool $status = true;
    private bool $disconnected = false;

    public function __construct(
        protected readonly AbstractEvent $event,
        public readonly string           $id,
    )
    {
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return array<class-string<EventContextInterface>>
     */
    public function listening(): array
    {
        return array_keys($this->listeners);
    }

    /**
     * @return bool
     */
    public function status(): bool
    {
        return $this->status && !$this->disconnected;
    }

    /**
     * @param AbstractEvent $event
     * @param EventContextInterface $context
     * @return mixed
     * @throws ListenerErrorException
     * @throws SubscriberNotListeningException
     * @throws SubscriptionClosedException
     */
    public function deliver(AbstractEvent $event, EventContextInterface $context): mixed
    {
        if (!$event instanceof $this->event) {
            throw new \LogicException("Event does not match subscription");
        }

        if (!$this->status || $this->disconnected) {
            throw new SubscriptionClosedException($this);
        }

        if (isset($this->listeners[$context::class])) {
            try {
                return ($this->listeners[$context::class])($context);
            } catch (\Throwable $t) {
                throw new ListenerErrorException($this, $context, $t);
            }
        }

        throw new SubscriberNotListeningException();
    }

    /**
     * @return $this
     * @throws SubscriptionClosedException
     * @api
     */
    public function ping(): static
    {
        if (!$this->status || $this->disconnected) {
            throw new SubscriptionClosedException($this);
        }

        return $this;
    }

    /**
     * @param class-string<EventContextInterface> $context
     * @param \Closure $callback
     * @return boolean
     */
    public function listen(string $context, \Closure $callback): bool
    {
        if (!$this->status || $this->disconnected) {
            return false;
        }

        if (!in_array($context, $this->event->contexts)) {
            throw new \OutOfBoundsException("Event does not support argument context");
        }

        $this->listeners[$context] = $callback;
        $this->event->isListening($this, $context);
        return true;
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return [
            "event" => $this->event,
            "status" => $this->status,
            "disconnected" => $this->disconnected,
            "id" => $this->id,
            "listeners" => [],
        ];
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->event = $data["event"];
        $this->status = $data["status"];
        $this->disconnected = $data["disconnected"];
        $this->id = $data["id"];
        $this->listeners = [];
    }

    /**
     * @return void
     */
    public function unsubscribe(): void
    {
        $this->status = false;
        $this->event->unsubscribe($this);
    }

    /**
     * @param AbstractEvent $event
     * @return void
     */
    public function disconnect(AbstractEvent $event): void
    {
        if ($event instanceof $this->event) {
            $this->disconnected = true;
        }
    }
}