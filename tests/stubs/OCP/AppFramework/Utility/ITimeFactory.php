<?php

declare(strict_types=1);

namespace OCP\AppFramework\Utility;

interface ITimeFactory {
    public function now(): \DateTimeImmutable;
}
