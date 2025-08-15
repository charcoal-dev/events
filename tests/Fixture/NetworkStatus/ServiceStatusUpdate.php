<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Tests\Fixture\NetworkStatus;

use Charcoal\Events\Contracts\BehaviourContextEnablerInterface;

/**
 * Class ServiceStatusUpdate
 * @package Charcoal\Events\Tests\Fixture\NetworkStatus
 */
readonly class ServiceStatusUpdate implements NetworkStatusEventContext,
    BehaviourContextEnablerInterface
{
    public function __construct(
        public bool               $serviceStatus,
        public \DateTimeImmutable $timestamp,
    )
    {
    }
}