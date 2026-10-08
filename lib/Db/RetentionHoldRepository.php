<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Db;

use DateTimeImmutable;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

final class RetentionHoldRepository implements RetentionHoldRepositoryInterface {
    public function __construct(private IDBConnection $db){}
    public function activeFor(string $policyId,string $recordReference):?array{$qb=$this->db->getQueryBuilder();$row=$qb->select('*')->from('flz_dp_retention_holds')->where($qb->expr()->eq('policy_id',$qb->createNamedParameter($policyId,IQueryBuilder::PARAM_STR)))->andWhere($qb->expr()->eq('record_ref',$qb->createNamedParameter($recordReference,IQueryBuilder::PARAM_STR)))->andWhere($qb->expr()->isNull('released_at'))->orderBy('placed_at','DESC')->setMaxResults(1)->executeQuery()->fetchAssociative();return$row===false?null:$row;}
    public function place(string $policyId,string $recordReference,string $reasonCode,string $evidenceReference,string $placedBy,DateTimeImmutable $placedAt,DateTimeImmutable $reviewDueAt):int{$qb=$this->db->getQueryBuilder();$qb->insert('flz_dp_retention_holds')->setValue('policy_id',$qb->createNamedParameter($policyId,IQueryBuilder::PARAM_STR))->setValue('record_ref',$qb->createNamedParameter($recordReference,IQueryBuilder::PARAM_STR))->setValue('reason_code',$qb->createNamedParameter($reasonCode,IQueryBuilder::PARAM_STR))->setValue('evidence_ref',$qb->createNamedParameter($evidenceReference,IQueryBuilder::PARAM_STR))->setValue('placed_by',$qb->createNamedParameter($placedBy,IQueryBuilder::PARAM_STR))->setValue('placed_at',$qb->createNamedParameter($placedAt,IQueryBuilder::PARAM_DATETIME_IMMUTABLE))->setValue('review_due_at',$qb->createNamedParameter($reviewDueAt,IQueryBuilder::PARAM_DATETIME_IMMUTABLE))->setValue('released_by',$qb->createNamedParameter(null,IQueryBuilder::PARAM_NULL))->setValue('released_at',$qb->createNamedParameter(null,IQueryBuilder::PARAM_NULL))->executeStatement();return$qb->getLastInsertId();}
    public function releaseActive(string $policyId,string $recordReference,string $releasedBy,DateTimeImmutable $releasedAt):bool{$qb=$this->db->getQueryBuilder();return$qb->update('flz_dp_retention_holds')->set('released_by',$qb->createNamedParameter($releasedBy,IQueryBuilder::PARAM_STR))->set('released_at',$qb->createNamedParameter($releasedAt,IQueryBuilder::PARAM_DATETIME_IMMUTABLE))->where($qb->expr()->eq('policy_id',$qb->createNamedParameter($policyId,IQueryBuilder::PARAM_STR)))->andWhere($qb->expr()->eq('record_ref',$qb->createNamedParameter($recordReference,IQueryBuilder::PARAM_STR)))->andWhere($qb->expr()->isNull('released_at'))->executeStatement()>0;}
}
