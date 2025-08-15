<?php
/**
 * Part of the "charcoal-dev/base" package.
 * @link https://github.com/charcoal-dev/base
 */

declare(strict_types=1);

namespace Charcoal\Events\Exceptions;

use Charcoal\Events\Contracts\EventContextInterface;
use Charcoal\Events\Subscriptions\Subscription;

/**
 * Class ListenerErrorException
 * @package Charcoal\Events\Exceptions
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