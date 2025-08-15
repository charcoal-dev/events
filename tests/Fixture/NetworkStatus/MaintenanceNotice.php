<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Tests\Fixture\NetworkStatus;

readonly class MaintenanceNotice implements NetworkStatusEventContext
{
    public function __construct(
        public string             $message,
        public \DateTimeImmutable $start,
        public \DateTimeImmutable $end,
        public \DateTimeImmutable $timestamp,
    )
    {
    }
}
