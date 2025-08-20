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
use Charcoal\Events\Exceptions\SubscriberNotListeningException;
use Charcoal\Events\Exceptions\SubscriptionClosedException;
use Charcoal\Events\Stats\EventStats;
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
    /** @var EventStats */
    private EventStats $stats;

    use ControlledSerializableTrait;

    /**
     * @param string $name
     * @param array<class-string<EventContextInterface>> $contexts
     */
    public function __construct(
        public readonly string $name,
        array                  $contexts
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
        $this->enableMonitoring();
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
            "stats" => null,
        ];
    }

    public function __debugInfo(): array
    {
        return [static::class, $this->primary];
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
        $this->enableMonitoring();
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
     * @param Subscription $subscriber
     * @param class-string<EventContextInterface> $eventContext
     * @return bool
     */
    public function isListening(Subscription $subscriber, string $eventContext): bool
    {
        if (in_array($eventContext, $subscriber->listening())) {
            if (!in_array($subscriber->id, $this->stats->history[$eventContext])) {
                $this->stats->history[$eventContext][] = $subscriber->id;
            }

            return true;
        }

        return false;
    }

    /**
     * @param EventContextInterface $context
     * @return DispatchReport
     * @api
     */
    protected function dispatchEvent(EventContextInterface $context): DispatchReport
    {
        if (!in_array($context::class, $this->contexts)) {
            throw new \OutOfBoundsException("Event does not support argument context");
        }

        $this->stats->emits[$context::class]++;
        if (!$this->subscribers) {
            return new DispatchReport($this, $context, [], 0);
        }

        $listeners = 0;
        $report = [];
        foreach ($this->subscribers as $subscriber => $subscription) {
            $status = ListenerResult::Uncertain;
            $listeners++;

            try {
                $result = $subscription->deliver($this, $context);
            } catch (SubscriptionClosedException) {
                unset($this->subscribers[$subscriber]);
                $status = ListenerResult::Closed;
            } catch (SubscriberNotListeningException) {
                $status = ListenerResult::NotListening;
                $listeners--;
            } catch (\Throwable $t) {
                $status = ListenerResult::Error;
                $report[$subscriber] = new SubscriberResult($subscription, $status, null, $t);
                continue;
            }

            $report[$subscriber] = new SubscriberResult($subscription, $status, $result ?? null);
        }

        return new DispatchReport($this, $context, $report, $listeners);
    }

    /**
     * Enables monitoring by initializing event statistics, including emitted counts,
     * history for each context, and the current state of events.
     */
    public function enableMonitoring(): void
    {
        $this->stats = new EventStats();
        $this->stats->emits = array_fill_keys($this->contexts, 0);
        $this->stats->history = array_fill_keys($this->contexts, []);
        $this->stats->current = [];
    }

    /**
     * Inspects and returns the event statistics, optionally converting
     * class names in the context keys to their base names.
     */
    public function inspect(bool $basename = true): EventStats
    {
        $inspect = new EventStats();
        $inspect->emits = $this->stats->emits;
        $inspect->history = $this->stats->history;
        $inspect->current = array_fill_keys($this->contexts, []);
        foreach ($this->subscribers as $subscriber) {
            foreach ($subscriber->listening() as $context) {
                $inspect->current[$context][] = $subscriber->id;
            }
        }

        if ($basename) {
            $contexts = array_map(fn($k) => ObjectHelper::baseClassName($k), array_keys($inspect->emits));
            $inspect->emits = array_combine($contexts, array_values($inspect->emits));
            $inspect->history = array_combine($contexts, array_values($inspect->history));
            $inspect->current = array_combine($contexts, array_values($inspect->current));
        }

        return $inspect;
    }
}