<?php

declare(strict_types=1);

namespace OCP\AppFramework\Http;

final class JSONResponse {
    /** @param array<string, mixed> $data */
    public function __construct(private array $data = [], private int $status = Http::STATUS_OK) {
    }

    /** @return array<string, mixed> */
    public function getData(): array {
        return $this->data;
    }

    public function getStatus(): int {
        return $this->status;
    }
}
