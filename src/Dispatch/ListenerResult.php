<?php
/**
 * Part of the "charcoal-dev/events" package.
 * @link https://github.com/charcoal-dev/events
 */

declare(strict_types=1);

namespace Charcoal\Events\Dispatch;

/**
 * Class ListenerResult
 * @package Charcoal\Events\Event
 */
enum ListenerResult
{
    case NotListening;
    case Success;
    case Error;
    case Closed;
    case Uncertain;
}