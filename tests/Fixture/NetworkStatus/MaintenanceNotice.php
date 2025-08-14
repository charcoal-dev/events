<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Tests\Fixture\NetworkStatus;

readonly class MaintenanceNotice extends BaseNetworkStatusEvent
{
    public function __construct(
        NetworkStatusEvent        $event,
        public string             $message,
        public \DateTimeImmutable $start,
        public \DateTimeImmutable $end,
        public \DateTimeImmutable $timestamp,
    )
    {
        parent::__construct($event);
    }
}
