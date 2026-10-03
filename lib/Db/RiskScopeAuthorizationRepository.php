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

final class RiskScopeAuthorizationRepository implements RiskScopeAuthorizationRepositoryInterface {
    private const TABLE = 'fdp_risk_scope_auth';

    public function __construct(private IDBConnection $db) {
    }

    public function latest(string $scopeId): ?array {
        $qb = $this->db->getQueryBuilder();
        $row = $qb->select('*')
            ->from(self::TABLE)
            ->where($qb->expr()->eq('scope_id', $qb->createNamedParameter($scopeId, IQueryBuilder::PARAM_STR)))
            ->orderBy('revision', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $row === false ? null : $this->mapRow($row);
    }

    public function history(string $scopeId): array {
        $qb = $this->db->getQueryBuilder();
        $rows = $qb->select('*')
            ->from(self::TABLE)
            ->where($qb->expr()->eq('scope_id', $qb->createNamedParameter($scopeId, IQueryBuilder::PARAM_STR)))
            ->orderBy('revision', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map([$this, 'mapRow'], $rows);
    }

    public function appendIfCurrent(array $configuration, int $expectedRevision): array {
        $scopeId = (string)$configuration['scopeId'];
        $actualRevision = (int)($this->latest($scopeId)['revision'] ?? 0);
        if ($actualRevision !== $expectedRevision) {
            throw new DomainException('Die Scope-Konfiguration wurde zwischenzeitlich geändert.');
        }

        $row = [
            ...$configuration,
            'revision' => $expectedRevision + 1,
        ];
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->insert(self::TABLE);
            foreach ($this->columns($row) as $column => [$value, $type]) {
                $qb->setValue($column, $qb->createNamedParameter($value, $type));
            }
            $qb->executeStatement();
        } catch (DatabaseException $error) {
            if ($error->getReason() === DatabaseException::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
                throw new DomainException('Die Scope-Konfiguration wurde zwischenzeitlich geändert.', 0, $error);
            }
            throw $error;
        }

        return $row;
    }

    /** @return array<string, array{0: mixed, 1: int}> */
    private function columns(array $row): array {
        return [
            'scope_id' => [$row['scopeId'], IQueryBuilder::PARAM_STR],
            'revision' => [$row['revision'], IQueryBuilder::PARAM_INT],
            'schema_version' => [$row['schemaVersion'], IQueryBuilder::PARAM_STR],
            'enabled' => [$row['enabled'], IQueryBuilder::PARAM_BOOL],
            'policy_revision' => [$row['policyRevision'], IQueryBuilder::PARAM_STR],
            'authorization_ref' => [$row['authorizationReference'], IQueryBuilder::PARAM_STR],
            'effective_at' => [new DateTimeImmutable($row['effectiveAt']), IQueryBuilder::PARAM_DATETIME_IMMUTABLE],
            'expires_at' => [new DateTimeImmutable($row['expiresAt']), IQueryBuilder::PARAM_DATETIME_IMMUTABLE],
            'dpo_confirmed' => [$row['dpoConfirmed'], IQueryBuilder::PARAM_BOOL],
            'created_at' => [new DateTimeImmutable($row['createdAt']), IQueryBuilder::PARAM_DATETIME_IMMUTABLE],
        ];
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): array {
        return [
            'scopeId' => (string)$row['scope_id'],
            'revision' => (int)$row['revision'],
            'schemaVersion' => (string)$row['schema_version'],
            'enabled' => (bool)$row['enabled'],
            'policyRevision' => (string)$row['policy_revision'],
            'authorizationReference' => (string)$row['authorization_ref'],
            'effectiveAt' => $this->date($row['effective_at'])->format(DATE_ATOM),
            'expiresAt' => $this->date($row['expires_at'])->format(DATE_ATOM),
            'dpoConfirmed' => (bool)$row['dpo_confirmed'],
            'createdAt' => $this->date($row['created_at'])->format(DATE_ATOM),
        ];
    }

    private function date(mixed $value): DateTimeImmutable {
        return $value instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($value)
            : new DateTimeImmutable((string)$value, new DateTimeZone('UTC'));
    }
}
