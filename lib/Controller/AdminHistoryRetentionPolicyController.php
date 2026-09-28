<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Controller;

use DomainException;
use OCA\FilzmannDataProtection\AppInfo\Application;
use OCA\FilzmannDataProtection\Service\AdminHistoryRetentionPolicyService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

final class AdminHistoryRetentionPolicyController extends Controller {
    public function __construct(IRequest $request,private AdminHistoryRetentionPolicyService $service){parent::__construct(Application::APP_ID,$request);}
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function show():JSONResponse{if(!$this->service->canConfigure())return $this->denied();return new JSONResponse(['retentionPolicy'=>$this->service->policy(),'history'=>$this->service->history()]);}
    #[NoAdminRequired]
    public function save(string $durationPeriod,int $expectedRevision):JSONResponse{if(!$this->service->canConfigure())return $this->denied();try{return new JSONResponse(['retentionPolicy'=>$this->service->save(compact('durationPeriod','expectedRevision'))]);}catch(DomainException $error){return new JSONResponse(['message'=>$error->getMessage()],Http::STATUS_CONFLICT);}catch(\Throwable){return new JSONResponse(['message'=>'Die Retention-Policy ist ungültig.'],Http::STATUS_BAD_REQUEST);}}
    #[NoAdminRequired]
    public function review(int $expectedRevision):JSONResponse{if(!$this->service->canConfigure())return $this->denied();try{return new JSONResponse(['review'=>$this->service->recordReview($expectedRevision),'retentionPolicy'=>$this->service->policy()]);}catch(DomainException $error){return new JSONResponse(['message'=>$error->getMessage()],Http::STATUS_CONFLICT);}catch(\Throwable){return new JSONResponse(['message'=>'Die Prüfung konnte nicht protokolliert werden.'],Http::STATUS_BAD_REQUEST);}}
    private function denied():JSONResponse{return new JSONResponse(['message'=>'Zugriff verweigert.'],Http::STATUS_FORBIDDEN);}
}
