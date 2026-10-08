<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Privacy;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use OCA\FlzDataProtection\AppInfo\Application;
use OCA\FlzDataProtection\Db\TemporaryAdminAccessRepositoryInterface;
use OCA\FlzDataProtection\PublicApi\V1\RetentionCandidate;
use OCA\FlzDataProtection\PublicApi\V1\RetentionPolicy;
use OCA\FlzDataProtection\PublicApi\V1\RetentionPreviewPage;
use OCA\FlzDataProtection\PublicApi\V1\RetentionPreviewRequest;
use OCA\FlzDataProtection\PublicApi\V1\RetentionProvider;
use OCA\FlzDataProtection\PublicApi\V1\RetentionProviderDescriptor;
use OCA\FlzDataProtection\Service\AdminHistoryRetentionPolicyService;

final class AdminHistoryRetentionProvider implements RetentionProvider {
    public const POLICY_ID='temporary_admin_access_history_review';
    public function __construct(private TemporaryAdminAccessRepositoryInterface $repository,private AdminHistoryRetentionPolicyService $settings){}
    public function descriptor(): RetentionProviderDescriptor{return new RetentionProviderDescriptor(Application::APP_ID,'Datenschutz-Center','1.0',200);}
    public function policies(): array {$policy=$this->settings->policy();return[new RetentionPolicy(self::POLICY_ID,'Adminfreigabehistorie','Prüfung beendeter app-lokaler Adminfreigaben','COMPLETED_AT',$policy['durationPeriod'],'REVIEW','1.'.$policy['revision'])];}
    public function preview(RetentionPreviewRequest $request): RetentionPreviewPage {
        if($request->policyId()!==self::POLICY_ID)return new RetentionPreviewPage('not_applicable');
        $offset=$this->offset($request->cursor());$policy=$this->settings->policy();$cutoff=(new DateTimeImmutable($request->evaluatedAt()))->sub(new DateInterval($policy['durationPeriod']));
        $rows=$this->repository->endedBefore($cutoff,$request->limit()+1,$offset);$hasMore=count($rows)>$request->limit();if($hasMore)array_pop($rows);
        $candidates=array_map(static function(array $grant):RetentionCandidate{$actualEnd=$grant['revokedAt']!==null&&$grant['revokedAt']<$grant['endsAt']?$grant['revokedAt']:$grant['endsAt'];return new RetentionCandidate(self::POLICY_ID,'admin-grant:'.$grant['id'],$actualEnd->format(DATE_ATOM),'REVIEW','Adminfreigabe endete vor dem konfigurierten REVIEW-Stichtag.');},$rows);
        return new RetentionPreviewPage($hasMore?'partial':'complete',$candidates,[],$hasMore?(string)($offset+$request->limit()):null);
    }
    private function offset(?string $cursor):int{if($cursor===null)return 0;if(!preg_match('/^(?:0|[1-9][0-9]{0,8})$/',$cursor))throw new InvalidArgumentException('Invalid admin-history retention cursor.');return(int)$cursor;}
}
