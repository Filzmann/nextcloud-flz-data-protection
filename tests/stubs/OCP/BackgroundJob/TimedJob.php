<?php

declare(strict_types=1);

namespace OCP\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;

abstract class TimedJob {
    public function __construct(ITimeFactory $time) {
    }

    protected function setInterval(int $seconds): void {
    }

    abstract protected function run($argument): void;
}
