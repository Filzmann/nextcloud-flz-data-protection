<?php

declare(strict_types=1);

namespace OCP\EventDispatcher;

interface IEventDispatcher {
    public function dispatchTyped(Event $event): Event;
}
