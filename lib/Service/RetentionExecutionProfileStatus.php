<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Service;

interface RetentionExecutionProfileStatus {
    public function status(): array;
}
