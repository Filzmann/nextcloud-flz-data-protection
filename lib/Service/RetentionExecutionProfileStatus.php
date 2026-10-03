<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Service;

interface RetentionExecutionProfileStatus {
    public function status(): array;
}
