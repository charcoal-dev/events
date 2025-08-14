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
readonly class ServiceStatusUpdate extends BaseNetworkStatusEvent
{
    public function __construct(
        NetworkStatusEvent        $event,
        public bool               $serviceStatus,
        public \DateTimeImmutable $timestamp,
    )
    {
        parent::__construct($event);
    }
}