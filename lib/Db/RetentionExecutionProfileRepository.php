<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Db;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

final class RetentionExecutionProfileRepository implements RetentionExecutionProfileRepositoryInterface {
    private const TABLE = 'fdp_retention_profile';

    public function __construct(private IDBConnection $db) {
    }

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

    public function append(array $configuration): array {
        $qb = $this->db->getQueryBuilder();
        $qb->insert(self::TABLE);
        foreach ($this->columns($configuration) as $column => [$value, $type]) {
            $qb->setValue($column, $qb->createNamedParameter($value, $type));
        }
        $qb->executeStatement();

        return $configuration;
    }

    /** @return array<string, array{0: mixed, 1: int}> */
    private function columns(array $row): array {
        return [
            'revision' => [$row['revision'], IQueryBuilder::PARAM_INT],
            'profile_id' => [$row['profileId'], IQueryBuilder::PARAM_STR],
            'profile_revision' => [$row['profileRevision'], IQueryBuilder::PARAM_STR],
            'legal_evidence_ref' => [$row['legalEvidenceReference'], IQueryBuilder::PARAM_STR],
            'scope_reference' => [$row['scopeReference'], IQueryBuilder::PARAM_STR],
            'purpose_reference' => [$row['purposeReference'], IQueryBuilder::PARAM_STR],
            'necessity_ref' => [$row['necessityAssessmentReference'], IQueryBuilder::PARAM_STR],
            'impact_ref' => [$row['impactAssessmentReference'], IQueryBuilder::PARAM_STR],
            'safeguards_ref' => [$row['safeguardsReference'], IQueryBuilder::PARAM_STR],
            'account_categories' => [$row['accountCategories'], IQueryBuilder::PARAM_STR],
            'all_accounts_employees' => [$row['allAccountsEmployeesConfirmed'], IQueryBuilder::PARAM_BOOL],
            'effective_at' => [$row['effectiveAt'], IQueryBuilder::PARAM_DATETIME_IMMUTABLE],
            'legal_review_due_at' => [$row['legalReviewDueAt'], IQueryBuilder::PARAM_DATETIME_IMMUTABLE],
            'backup_regular_days' => [$row['backupRegularDays'], IQueryBuilder::PARAM_INT],
            'backup_buffer_days' => [$row['backupBufferDays'], IQueryBuilder::PARAM_INT],
            'backup_responsible' => [$row['backupResponsibleParty'], IQueryBuilder::PARAM_STR],
            'backup_scope' => [$row['backupScope'], IQueryBuilder::PARAM_STR],
            'backup_evidence_ref' => [$row['backupEvidenceReference'], IQueryBuilder::PARAM_STR],
            'backup_evidence_at' => [$row['backupEvidenceAt'], IQueryBuilder::PARAM_DATETIME_IMMUTABLE],
            'backup_review_due_at' => [$row['backupReviewDueAt'], IQueryBuilder::PARAM_DATETIME_IMMUTABLE],
            'restore_test_ref' => [$row['restoreTestReference'], IQueryBuilder::PARAM_STR],
            'restore_tested_at' => [$row['restoreTestedAt'], IQueryBuilder::PARAM_DATETIME_IMMUTABLE],
            'dpo_confirmed' => [$row['dpoConfirmed'], IQueryBuilder::PARAM_BOOL],
            'changed_by' => [$row['changedBy'], IQueryBuilder::PARAM_STR],
            'created_at' => [$row['createdAt'], IQueryBuilder::PARAM_DATETIME_IMMUTABLE],
        ];
    }

    private function mapRow(array $row): array {
        return [
            'revision' => (int)$row['revision'],
            'profileId' => (string)$row['profile_id'],
            'profileRevision' => (string)$row['profile_revision'],
            'legalEvidenceReference' => (string)$row['legal_evidence_ref'],
            'scopeReference' => (string)$row['scope_reference'],
            'purposeReference' => (string)$row['purpose_reference'],
            'necessityAssessmentReference' => (string)$row['necessity_ref'],
            'impactAssessmentReference' => (string)$row['impact_ref'],
            'safeguardsReference' => (string)$row['safeguards_ref'],
            'accountCategories' => (string)$row['account_categories'],
            'allAccountsEmployeesConfirmed' => (bool)$row['all_accounts_employees'],
            'effectiveAt' => $this->date($row['effective_at']),
            'legalReviewDueAt' => $this->date($row['legal_review_due_at']),
            'backupRegularDays' => (int)$row['backup_regular_days'],
            'backupBufferDays' => (int)$row['backup_buffer_days'],
            'backupResponsibleParty' => (string)$row['backup_responsible'],
            'backupScope' => (string)$row['backup_scope'],
            'backupEvidenceReference' => (string)$row['backup_evidence_ref'],
            'backupEvidenceAt' => $this->date($row['backup_evidence_at']),
            'backupReviewDueAt' => $this->date($row['backup_review_due_at']),
            'restoreTestReference' => (string)$row['restore_test_ref'],
            'restoreTestedAt' => $this->date($row['restore_tested_at']),
            'dpoConfirmed' => (bool)$row['dpo_confirmed'],
            'changedBy' => (string)$row['changed_by'],
            'createdAt' => $this->date($row['created_at']),
        ];
    }

    private function date(mixed $value): DateTimeImmutable {
        return $value instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($value)
            : new DateTimeImmutable((string)$value, new DateTimeZone('UTC'));
    }
}
