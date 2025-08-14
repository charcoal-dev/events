<?php
/**
 * Part of the "charcoal-dev/base" package.
 * @link https://github.com/charcoal-dev/base
 */

declare(strict_types=1);

namespace Charcoal\Events\Exception;

use Charcoal\Events\Contracts\EventContextInterface;
use Charcoal\Events\Listener\Subscription;

/**
 * EventListenerErrorException
 * Represents an exception that encapsulates an error occurring in an event listener.
 */
class ListenerErrorException extends \Exception
{
    public function __construct(
        public readonly Subscription          $subscription,
        public readonly EventContextInterface $context,
        \Throwable                            $previous,
    )
    {
        parent::__construct(previous: $previous);
    }
}