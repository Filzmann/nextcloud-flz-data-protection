<?php

declare(strict_types=1);

namespace OCP\Migration;

interface IOutput {
    public function debug(string $message): void;
    public function info($message);
    public function warning($message);
    public function startProgress($max = 0);
    public function advance($step = 1, $description = '');
    public function finishProgress();
}
