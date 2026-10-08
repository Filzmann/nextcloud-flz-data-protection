<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Privacy;

use DateInterval;
use DateTimeImmutable;
use OCA\FlzDataProtection\AppInfo\Application;
use OCA\FlzDataProtection\Db\RetentionHoldRepositoryInterface;
use OCA\FlzDataProtection\Db\ExecutableTemporaryAdminAccessRepositoryInterface;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionBatch;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionCandidate;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionPage;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionPolicy;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionProvider;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionProviderDescriptor;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionRequest;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionResult;
use OCP\IDBConnection;
use Throwable;

final class AdminHistoryRetentionExecutionProvider implements RetentionExecutionProvider {
    public const POLICY_ID='temporary_admin_access_history_delete';
    public function __construct(private ExecutableTemporaryAdminAccessRepositoryInterface $repository,private RetentionHoldRepositoryInterface $holds,private IDBConnection $db){}
    public function descriptor():RetentionExecutionProviderDescriptor{return new RetentionExecutionProviderDescriptor(Application::APP_ID,'Datenschutz-Center','2.0',100);}
    public function policies():array{return[new RetentionExecutionPolicy(self::POLICY_ID,'Adminfreigabehistorie','Vollständige Löschung sechs Monate nach tatsächlichem Ende','COMPLETED_AT','P6M','DELETE','2.0')];}
    public function plan(RetentionExecutionRequest $request):RetentionExecutionPage{$this->assertRequest($request);$cutoff=(new DateTimeImmutable($request->evaluatedAt()))->sub(new DateInterval('P6M'));$rows=$this->repository->endedBefore($cutoff,$request->limit()*2,0);$candidates=[];$held=[];foreach($rows as$row){$end=$this->actualEnd($row);$ref='admin-grant:'.$row['id'];if($this->holds->activeFor(self::POLICY_ID,$ref)!==null){$held[]=$ref;continue;}$candidates[]=new RetentionExecutionCandidate(self::POLICY_ID,$ref,$end->format(DATE_ATOM),'DELETE','2.0',$this->token($ref,$end));if(count($candidates)>=$request->limit())break;}return new RetentionExecutionPage($candidates,$held);}
    public function execute(RetentionExecutionBatch $batch):RetentionExecutionResult{$this->assertRequest($batch->request());$deleted=[];$held=[];$stale=[];$failed=[];foreach($batch->candidates()as$candidate){$ref=$candidate->reference();$this->db->beginTransaction();try{if(!preg_match('/^admin-grant:([1-9][0-9]*)$/',$ref,$m)){$status='stale';}else{$row=$this->repository->findForUpdate((int)$m[1]);if($row===null){$status='stale';}else{$end=$this->actualEnd($row);if($end->add(new DateInterval('P6M'))>new DateTimeImmutable($batch->request()->evaluatedAt())||!hash_equals($candidate->executionToken(),$this->token($ref,$end)))$status='stale';elseif($this->holds->activeFor(self::POLICY_ID,$ref)!==null)$status='held';else$status=$this->repository->deleteIfActualEnd((int)$m[1],$end)?'deleted':'stale';}}$this->db->commit();${$status}[]=$ref;}catch(Throwable){$this->db->rollBack();$failed[]=$ref;}}return new RetentionExecutionResult($deleted,$held,$stale,$failed);}
    private function assertRequest(RetentionExecutionRequest $request):void{if($request->policyId()!==self::POLICY_ID||$request->policyVersion()!=='2.0')throw new \DomainException('Retention policy version is unavailable.');}
    private function actualEnd(array$row):DateTimeImmutable{return$row['revokedAt']!==null&&$row['revokedAt']<$row['endsAt']?$row['revokedAt']:$row['endsAt'];}
    private function token(string$reference,DateTimeImmutable$end):string{return hash('sha256',self::POLICY_ID.'|'.$reference.'|'.$end->format(DATE_ATOM).'|2.0');}
}
