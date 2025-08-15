<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events;

use Charcoal\Events\Contracts\BehaviourContextEnablerInterface;
use Charcoal\Events\Contracts\EventContextInterface;
use Charcoal\Events\Dispatch\DispatchReport;
use Charcoal\Events\Subscriptions\Subscription;

/**
 * An abstract class that extends the functionality of AbstractEvent.
 * This class introduces a mechanism to queue event contexts and manage them accordingly
 * based on listener availability. It primarily deals with dispatching events and
 * ensuring the necessary actions are triggered when specific conditions are met.
 * @see BehaviourContextEnablerInterface
 */
abstract class BehaviorEvent extends AbstractEvent
{
    /** @var array<class-string<EventContextInterface>,EventContextInterface> */
    protected array $queue = [];

    /**
     * @param EventContextInterface $context
     * @return DispatchReport
     */
    protected function dispatchEvent(EventContextInterface $context): DispatchReport
    {
        $report = parent::dispatchEvent($context);
        if ($report->listenerCount === 0 && $context instanceof BehaviourContextEnablerInterface) {
            $this->queue[$context::class] = $context;
        }

        return $report;
    }

    /**
     * @param Subscription $subscriber
     * @param string $eventContext
     * @return bool
     */
    public function isListening(Subscription $subscriber, string $eventContext): bool
    {
        $isListening = parent::isListening($subscriber, $eventContext);
        if (isset($this->queue[$eventContext])) {
            $this->dispatchEvent($this->queue[$eventContext]);
            unset($this->queue[$eventContext]);
        }

        return $isListening;
    }
}