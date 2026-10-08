<?php

declare(strict_types=1);

use OCA\FlzDataProtection\Db\RetentionHoldRepositoryInterface;
use OCA\FlzDataProtection\Db\ExecutableTemporaryAdminAccessRepositoryInterface;
use OCA\FlzDataProtection\Privacy\AdminHistoryRetentionExecutionProvider;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionBatch;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionRequest;

$repository=new class implements ExecutableTemporaryAdminAccessRepositoryInterface{
    public array $rows=[7=>['id'=>7,'targetUid'=>'admin-a','grantedBy'=>'dpo','startsAt'=>null,'endsAt'=>null,'revokedAt'=>null,'revokedBy'=>null,'createdAt'=>null]];
    public function __construct(){ $this->rows[7]['startsAt']=new DateTimeImmutable('2026-02-28T09:00:00+00:00');$this->rows[7]['endsAt']=new DateTimeImmutable('2026-03-01T09:00:00+00:00');$this->rows[7]['createdAt']=$this->rows[7]['startsAt']; }
    public function replaceActive(string $targetUid,string $grantedBy,DateTimeImmutable $startsAt,DateTimeImmutable $endsAt):array{return[];}public function revokeActive(string $targetUid,string $revokedBy,DateTimeImmutable $revokedAt):bool{return false;}public function activeFor(string $targetUid,DateTimeImmutable $at):?array{return null;}public function history():array{return[];}public function historyForUid(string $uid,int $limit,DateTimeImmutable $asOf):array{return[];}
    public function endedBefore(DateTimeImmutable $cutoff,int $limit,int $offset):array{return array_slice(array_values(array_filter($this->rows,static fn($r)=>$r['endsAt']<=$cutoff)),$offset,$limit);}
    public function findForUpdate(int $id):?array{return$this->rows[$id]??null;}
    public function deleteIfActualEnd(int $id,DateTimeImmutable $actualEnd):bool{if(!isset($this->rows[$id])||$this->rows[$id]['endsAt']!=$actualEnd)return false;unset($this->rows[$id]);return true;}
};
$holds=new class implements RetentionHoldRepositoryInterface{public array $held=[];public function activeFor(string $policyId,string $reference):?array{return isset($this->held[$policyId.':'.$reference])?['id'=>1]:null;}public function place(string $policyId,string $recordReference,string $reasonCode,string $evidenceReference,string $placedBy,DateTimeImmutable $placedAt,DateTimeImmutable $reviewDueAt):int{return 1;}public function releaseActive(string $policyId,string $recordReference,string $releasedBy,DateTimeImmutable $releasedAt):bool{return true;}};
$db=new class implements OCP\IDBConnection{public function beginTransaction():void{}public function commit():void{}public function rollBack():void{}};
$provider=new AdminHistoryRetentionExecutionProvider($repository,$holds,$db);
$policy=$provider->policies()[0];$request=new RetentionExecutionRequest($policy->policyId(),$policy->version(),'2026-09-29T10:00:00+00:00',100);$plan=$provider->plan($request);
if(array_map(static fn($c)=>$c->reference(),$plan->candidates())!==['admin-grant:7'])throw new RuntimeException('Fällige Datenschutz-Center-Adminhistorie fehlt im Dry Run.');
$result=$provider->execute(new RetentionExecutionBatch($request,$plan->candidates()));
if($result->deletedReferences()!==['admin-grant:7']||isset($repository->rows[7]))throw new RuntimeException('Datenschutz-Center-Adminhistorie wird nicht nach P6M gelöscht.');
if($repository->activeFor('admin-a',new DateTimeImmutable('2026-09-29T10:00:00+00:00'))!==null)throw new RuntimeException('Gelöschte Historie reaktiviert einen Adminzugriff.');
echo "Data Protection admin-history execution provider tests passed.\n";
