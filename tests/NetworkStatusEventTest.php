<?php
declare(strict_types=1);

namespace Charcoal\Events\Tests;

use Charcoal\Events\Dispatch\ListenerResult;
use Charcoal\Events\Tests\Fixture\NetworkStatus\NetworkStatusEventContext;
use Charcoal\Events\Tests\Fixture\NetworkStatus\NetworkStatusEvent;
use Charcoal\Events\Tests\Fixture\NetworkStatus\ServiceStatusUpdate;
use PHPUnit\Framework\TestCase;

final class NetworkStatusEventTest extends TestCase
{
    private function newEvent(): NetworkStatusEvent
    {
        return new NetworkStatusEvent(
            'network-status',
            [
                NetworkStatusEventContext::class,   // primary
                ServiceStatusUpdate::class,      // allowed context
            ]
        );
    }

    private function newContext(bool $serviceStatus = true): ServiceStatusUpdate
    {
        return new ServiceStatusUpdate(
            $serviceStatus,
            new \DateTimeImmutable('now')
        );
    }

    /**
     * @return void
     * @throws \Charcoal\Events\Exceptions\SubscriptionClosedException
     */
    public function test_subscribes_and_dispatches_successfully(): void
    {
        $event = $this->newEvent();
        $subscription = $event->subscribe();

        $subscription->listen(ServiceStatusUpdate::class, function (ServiceStatusUpdate $ctx): string {
            $this->assertTrue($ctx->serviceStatus);
            return 'delivered';
        });

        $report = $event->dispatch($this->newContext(true));

        $this->assertSame($event, $report->event);
        $this->assertInstanceOf(ServiceStatusUpdate::class, $report->context);
        $this->assertCount(1, $report->result);

        $result = current($report->result);
        $this->assertSame(ListenerResult::Uncertain, $result->status, 'Successful delivery should be reported as Uncertain per current behavior');
        $this->assertSame('delivered', $result->result);
    }

    public function test_not_listening_when_no_listener_registered(): void
    {
        $event = $this->newEvent();
        $subscription = $event->subscribe();
        $this->assertNotEmpty($subscription->id());

        $report = $event->dispatch($this->newContext());

        $this->assertCount(1, $report->result);
        $result = current($report->result);
        $this->assertSame(ListenerResult::NotListening, $result->status);
        $this->assertNull($result->result);
    }

    public function test_closed_when_subscription_disconnected_and_removed_after_dispatch(): void
    {
        $event = $this->newEvent();
        $subscription = $event->subscribe();

        // Disconnect (remains registered but inactive)
        $subscription->disconnect($event);
        $this->assertSame(1, $event->count());

        $report = $event->dispatch($this->newContext());

        $this->assertCount(1, $report->result);
        $result = current($report->result);
        $this->assertSame(ListenerResult::Closed, $result->status);

        // After a Closed result, it should be removed from the event
        $this->assertSame(0, $event->count());
    }

    public function test_unsubscribe_removes_subscription_and_no_results_on_dispatch(): void
    {
        $event = $this->newEvent();
        $subscription = $event->subscribe();
        $this->assertSame(1, $event->count());

        $subscription->unsubscribe();
        $this->assertSame(0, $event->count());

        $report = $event->dispatch($this->newContext());
        $this->assertCount(0, $report->result);
    }

    /**
     * @return void
     * @throws \Charcoal\Events\Exceptions\SubscriptionClosedException
     */
    public function test_purge_inactive_removes_only_inactive(): void
    {
        $event = $this->newEvent();
        $active = $event->subscribe();
        $inactive = $event->subscribe();
        $inactive->disconnect($event); // mark inactive but keep registered

        $this->assertSame(2, $event->count());

        $event->purgeInactive();

        // Expect only the inactive one purged; active remains
        $this->assertSame(1, $event->count());

        // Dispatch should reach the active subscription
        $active->listen(ServiceStatusUpdate::class, fn() => 'ok');
        $report = $event->dispatch($this->newContext());
        $this->assertCount(1, $report->result);

        $result = current($report->result);
        $this->assertSame(ListenerResult::Uncertain, $result->status);
        $this->assertSame('ok', $result->result);
    }

    /**
     * @return void
     * @throws \Charcoal\Events\Exceptions\SubscriptionClosedException
     */
    public function test_serialization_persists_subscriptions_but_drops_listeners(): void
    {
        $event = $this->newEvent();
        $subscription = $event->subscribe();

        $subscription->listen(ServiceStatusUpdate::class, fn() => 'before-serialize');

        $serialized = serialize($event);
        $restored = unserialize($serialized);

        // Ensure we have the same number of subscriptions after restore
        $this->assertSame(1, $restored->count());

        // After wakeup, listeners are discarded, so dispatch should be NotListening
        $report = $restored->dispatch($this->newContext());
        $this->assertCount(1, $report->result);

        $result = current($report->result);
        $this->assertSame(ListenerResult::NotListening, $result->status);
        $this->assertNull($result->result);
    }
}