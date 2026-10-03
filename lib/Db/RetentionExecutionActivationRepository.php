<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Db;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DomainException;
use OCP\DB\Exception as DatabaseException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

final class RetentionExecutionActivationRepository implements RetentionExecutionActivationRepositoryInterface {
    private const TABLE = 'fdp_retention_exec';

    public function __construct(private IDBConnection $db) {}

    public function latest(): ?array {
        $qb = $this->db->getQueryBuilder();
        $row = $qb->select('*')
            ->from(self::TABLE)
            ->orderBy('revision', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $row === false ? null : $this->mapRow($row);
    }

    public function history(): array {
        $qb = $this->db->getQueryBuilder();
        $rows = $qb->select('*')
            ->from(self::TABLE)
            ->orderBy('revision', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map([$this, 'mapRow'], $rows);
    }

    public function historyForUid(string $uid, int $limit, int $offset, DateTimeImmutable $asOf): array {
        $qb = $this->db->getQueryBuilder();
        $rows = $qb->select('*')
            ->from(self::TABLE)
            ->where($qb->expr()->eq('changed_by', $qb->createNamedParameter($uid, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->lte('created_at', $qb->createNamedParameter($asOf, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->orderBy('created_at', 'ASC')
            ->addOrderBy('id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map([$this, 'mapRow'], $rows);
    }

    public function appendIfCurrent(array $configuration, int $expectedRevision): array {
        $actualRevision = (int)($this->latest()['revision'] ?? 0);
        if ($actualRevision !== $expectedRevision) {
            throw new DomainException('Die technische Aktivierung wurde zwischenzeitlich geändert.');
        }

        $row = [...$configuration, 'revision' => $expectedRevision + 1];
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->insert(self::TABLE);
            foreach ($this->columns($row) as $column => [$value, $type]) {
                $qb->setValue($column, $qb->createNamedParameter($value, $type));
            }
            $qb->executeStatement();
        } catch (DatabaseException $error) {
            if ($error->getReason() === DatabaseException::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
                throw new DomainException('Die technische Aktivierung wurde zwischenzeitlich geändert.', 0, $error);
            }
            throw $error;
        }

        return $row;
    }

    /** @return array<string, array{0:mixed,1:int}> */
    private function columns(array $row): array {
        return [
            'revision' => [$row['revision'], IQueryBuilder::PARAM_INT],
            'enabled' => [$row['enabled'], IQueryBuilder::PARAM_BOOL],
            'approved_policy_ids' => [json_encode($row['approvedPolicyIds'], JSON_THROW_ON_ERROR), IQueryBuilder::PARAM_STR],
            'backup_regular_days' => [$row['backupRegularDays'], IQueryBuilder::PARAM_INT],
            'backup_buffer_days' => [$row['backupBufferDays'], IQueryBuilder::PARAM_INT],
            'backup_verified_at' => $this->dateParameter($row['backupVerifiedAt']),
            'restore_verified_at' => $this->dateParameter($row['restoreVerifiedAt']),
            'verification_due_at' => $this->dateParameter($row['verificationDueAt']),
            'changed_by' => [$row['changedBy'], IQueryBuilder::PARAM_STR],
            'created_at' => [$row['createdAt'], IQueryBuilder::PARAM_DATETIME_IMMUTABLE],
        ];
    }

    /** @return array{0:mixed,1:int} */
    private function dateParameter(?DateTimeImmutable $value): array {
        return $value === null
            ? [null, IQueryBuilder::PARAM_NULL]
            : [$value, IQueryBuilder::PARAM_DATETIME_IMMUTABLE];
    }

    private function mapRow(array $row): array {
        return [
            'revision' => (int)$row['revision'],
            'enabled' => (bool)$row['enabled'],
            'approvedPolicyIds' => $this->policyIds($row['approved_policy_ids'] ?? '[]'),
            'backupRegularDays' => (int)$row['backup_regular_days'],
            'backupBufferDays' => (int)$row['backup_buffer_days'],
            'backupVerifiedAt' => $this->optionalDate($row['backup_verified_at'] ?? null),
            'restoreVerifiedAt' => $this->optionalDate($row['restore_verified_at'] ?? null),
            'verificationDueAt' => $this->optionalDate($row['verification_due_at'] ?? null),
            'changedBy' => (string)$row['changed_by'],
            'createdAt' => $this->date($row['created_at']),
        ];
    }

    private function optionalDate(mixed $value): ?DateTimeImmutable {
        return $value === null ? null : $this->date($value);
    }

    private function date(mixed $value): DateTimeImmutable {
        return $value instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($value)
            : new DateTimeImmutable((string)$value, new DateTimeZone('UTC'));
    }

    /** @return list<string> */
    private function policyIds(mixed $encoded): array {
        try {
            $value = json_decode((string)$encoded, true, flags: JSON_THROW_ON_ERROR);
            return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
        } catch (\Throwable) {
            return [];
        }
    }
}
