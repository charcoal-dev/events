<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events;

use Charcoal\Base\Support\Helpers\ObjectHelper;
use Charcoal\Base\Traits\ControlledSerializableTrait;
use Charcoal\Events\Contracts\EventContextInterface;
use Charcoal\Events\Dispatch\DispatchReport;
use Charcoal\Events\Dispatch\ListenerResult;
use Charcoal\Events\Dispatch\SubscriberResult;
use Charcoal\Events\Exception\SubscriberNotListeningException;
use Charcoal\Events\Exception\SubscriptionClosedException;
use Charcoal\Events\Subscriptions\Subscription;

/**
 * Represents an event that allows attaching and managing listeners.
 * @property class-string<EventContextInterface> $primary
 */
abstract class AbstractEvent
{
    /** @var array<class-string<EventContextInterface>> $contexts */
    public readonly array $contexts;
    /** @var array<string,Subscription> */
    private array $subscribers = [];

    use ControlledSerializableTrait;

    /**
     * @param string $name
     * @param string $primary
     * @param array $contexts
     */
    public function __construct(
        public readonly string $name,
        public readonly string $primary,
        array                  $contexts,
    )
    {
        if (!$this->primary || !is_subclass_of($this->primary, EventContextInterface::class, true)) {
            throw new \LogicException(ObjectHelper::baseClassName($this) . " expects subclass of " .
                "EventContextInterface as primary context, got " .
                ObjectHelper::baseClassName($this->primary));
        }


        foreach ($contexts as $context) {
            if (!is_subclass_of($context, $this->primary, true)) {
                throw new \LogicException(ObjectHelper::baseClassName($this) . " expects child of " .
                    ObjectHelper::baseClassName(($this->primary) . " as context, got " .
                        ObjectHelper::baseClassName($context)));
            }
        }

        $this->contexts = array_unique([...$contexts, $this->primary]);
    }

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        return [
            "name" => $this->name,
            "primary" => $this->primary,
            "contexts" => $this->contexts,
            "subscribers" => $this->subscribers,
        ];
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->name = $data["name"];
        $this->primary = $data["primary"];
        $this->contexts = $data["contexts"];
        $this->subscribers = $data["subscribers"];
    }

    /**
     * @return void
     */
    public function purgeInactive(): void
    {
        foreach ($this->subscribers as $id => $subscriber) {
            if (!$subscriber->status()) {
                unset($this->subscribers[$id]);
            }
        }

        $this->subscribers = [];
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->subscribers);
    }

    /**
     * @param mixed $nonce
     * @return string
     */
    abstract protected function generateSubscriptionId(mixed $nonce): string;

    /**
     * @param mixed $nonce
     * @return Subscription
     */
    public function subscribe(mixed $nonce): Subscription
    {
        $subscriber = $this->generateSubscriptionId($nonce);
        $subscription = new Subscription($this, $subscriber);
        $this->subscribers[$subscriber] = $subscription;
        return $subscription;
    }

    /**
     * @param Subscription $subscriber
     * @return void
     */
    public function unsubscribe(Subscription $subscriber): void
    {
        unset($this->subscribers[$subscriber->id]);
    }

    /**
     * @param EventContextInterface $context
     * @return DispatchReport
     */
    public function dispatch(EventContextInterface $context): DispatchReport
    {
        if (!is_a($context, $this->primary, true)) {
            throw new \LogicException(ObjectHelper::baseClassName($this) . " expects subclass of " .
                ObjectHelper::baseClassName(($this->primary) . " as context, got " .
                    ObjectHelper::baseClassName($context)));
        }

        if (!$this->subscribers) {
            return new DispatchReport($this, $context, []);
        }

        $report = [];
        foreach ($this->subscribers as $subscriber => $subscription) {
            $status = ListenerResult::Uncertain;

            try {
                $result = $subscription->deliver($this, $context);
            } catch (SubscriptionClosedException) {
                unset($this->subscribers[$subscriber]);
                $status = ListenerResult::Closed;
            } catch (SubscriberNotListeningException) {
                $status = ListenerResult::NotListening;
            } catch (\Throwable $t) {
                $status = ListenerResult::Error;
                $report[$subscriber] = new SubscriberResult($subscription, $status, null, $t);
                continue;
            }

            $report[$subscriber] = new SubscriberResult($subscription, $status, $result ?? null);
        }

        return new DispatchReport($this, $context, $report);
    }
}