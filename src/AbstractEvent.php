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
    /** @var class-string<EventContextInterface> */
    public readonly string $primary;
    /** @var array<class-string<EventContextInterface>> $contexts */
    public readonly array $contexts;
    /** @var array<string,Subscription> */
    private array $subscribers = [];

    use ControlledSerializableTrait;

    /**
     * @param string $name
     * @param array<class-string<EventContextInterface>> $contexts
     */
    public function __construct(
        public readonly string $name,
        array                  $contexts,
    )
    {

        $contexts = array_unique($contexts);
        if (!$contexts) {
            throw new \LogicException(ObjectHelper::baseClassName($this) . " expects at least one context");
        }

        $this->primary = array_shift($contexts);
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

        $this->contexts = [$this->primary, ...$contexts];
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
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->subscribers);
    }

    /**
     * @return array<string,Subscription>
     */
    public function subscribers(): array
    {
        return $this->subscribers;
    }

    /**
     * @param string $uniqId
     * @return Subscription
     * @api
     */
    protected function createSubscription(string $uniqId): Subscription
    {
        $subscription = new Subscription($this, $uniqId);
        $this->subscribers[$uniqId] = $subscription;
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
     * @api
     */
    protected function dispatchEvent(EventContextInterface $context): DispatchReport
    {
        if (!$context->getEvent() instanceof $this) {
            throw new \LogicException("Event does not match subscription");
        } elseif (!in_array($context::class, $this->contexts)) {
            throw new \OutOfBoundsException("Event does not support argument context");
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