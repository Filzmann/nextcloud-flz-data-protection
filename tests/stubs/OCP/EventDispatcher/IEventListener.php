<?php

declare(strict_types=1);

namespace OCP\EventDispatcher;

interface IEventListener {
    public function handle(Event $event): void;
}
