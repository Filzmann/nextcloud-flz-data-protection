<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Db;

use DateTimeImmutable;

interface ExecutableTemporaryAdminAccessRepositoryInterface extends TemporaryAdminAccessRepositoryInterface {
    public function findForUpdate(int $id): ?array;
    public function deleteIfActualEnd(int $id, DateTimeImmutable $actualEnd): bool;
}
