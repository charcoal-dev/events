<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Tests\Fixture\NetworkStatus;

/**
 * Class ServiceStatusUpdate
 * @package Charcoal\Events\Tests\Fixture\NetworkStatus
 */
readonly class ServiceStatusUpdate implements NetworkStatusEventContext
{
    public function __construct(
        public bool               $serviceStatus,
        public \DateTimeImmutable $timestamp,
    )
    {
    }
}