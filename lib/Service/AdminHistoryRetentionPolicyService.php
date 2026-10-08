<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Service;

use DateInterval;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use OCA\FlzDataProtection\AppInfo\Application;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUserSession;
use Throwable;

/** Versionierte, nichtdestruktive Policyquelle für die eigene Adminfreigabehistorie. */
final class AdminHistoryRetentionPolicyService {
    public const CONFIGURATOR_GROUP = 'Datenschutzbeauftragte';
    private const KEY = 'admin_history_retention_policy_v1';
    private const DEFAULT_PERIOD = 'P6M';

    public function __construct(private IAppConfig $config,private IGroupManager $groups,private IUserSession $session,private ITimeFactory $clock) {}

    public function canConfigure(): bool {
        $uid=$this->session->getUser()?->getUID()??'';
        if($uid==='') return false;
        try{return $this->groups->isInGroup($uid,self::CONFIGURATOR_GROUP);}catch(Throwable){return false;}
    }
    public function policy(): array {
        $history=$this->history();$entry=$history===[]?null:$history[array_key_last($history)];
        return ['durationPeriod'=>$entry['durationPeriod']??self::DEFAULT_PERIOD,'action'=>'REVIEW','revision'=>$entry['revision']??0,'effectiveAt'=>$entry['effectiveAt']??null,'reviewedAt'=>$entry['reviewedAt']??null,'changedBy'=>$entry['changedBy']??null,'reviewDue'=>$this->reviewDue()];
    }
    public function save(array $policy): array {
        $uid=$this->requireConfigurator();$period=$this->validatePeriod($policy['durationPeriod']??null);$expected=$this->validateExpectedRevision($policy['expectedRevision']??null);$history=$this->history();$this->assertRevision($history,$expected);$now=$this->clock->now()->format(DATE_ATOM);
        $history[]=['revision'=>$expected+1,'event'=>'configured','durationPeriod'=>$period,'effectiveAt'=>$now,'reviewedAt'=>$now,'changedBy'=>$uid];$this->persist($history);return $this->policy();
    }
    public function recordReview(int $expectedRevision): array {
        $uid=$this->requireConfigurator();$history=$this->history();$this->assertRevision($history,$expectedRevision);$current=$history===[]?['durationPeriod'=>self::DEFAULT_PERIOD,'effectiveAt'=>null]:$history[array_key_last($history)];
        $history[]=['revision'=>$expectedRevision+1,'event'=>'reviewed','durationPeriod'=>$current['durationPeriod'],'effectiveAt'=>$current['effectiveAt'],'reviewedAt'=>$this->clock->now()->format(DATE_ATOM),'changedBy'=>$uid];$this->persist($history);return $history[array_key_last($history)];
    }
    public function history(): array {
        $history=$this->config->getValueArray(Application::APP_ID,self::KEY,[],true);if($history===[])return[];
        try{if(!is_array($history))throw new InvalidArgumentException();$validated=[];
            foreach($history as $index=>$entry){if(!is_array($entry)||($entry['revision']??null)!==$index+1||!in_array($entry['event']??null,['configured','reviewed'],true)||!is_string($entry['changedBy']??null)||trim($entry['changedBy'])==='')throw new InvalidArgumentException();$entry['durationPeriod']=$this->validatePeriod($entry['durationPeriod']??null);foreach(['effectiveAt','reviewedAt']as$field)if($entry[$field]!==null)new DateTimeImmutable((string)$entry[$field]);$validated[]=$entry;}return$validated;
        }catch(Throwable $error){throw new DomainException('Die gespeicherte Retention-Policyhistorie ist beschädigt.',0,$error);}
    }
    public function reviewDue(): bool {$history=$this->history();if($history===[])return true;$latest=$history[array_key_last($history)];$baseline=$latest['reviewedAt']??$latest['effectiveAt']??null;return is_string($baseline)&&$this->clock->now()>=(new DateTimeImmutable($baseline))->add(new DateInterval('P1Y'));}
    private function requireConfigurator(): string {$uid=$this->session->getUser()?->getUID()??'';if($uid===''||!$this->canConfigure())throw new DomainException('Zugriff verweigert.');return$uid;}
    private function validatePeriod(mixed $period): string {if(!is_string($period)||!preg_match('/^P([1-9][0-9]*)([YMD])$/',$period,$matches))throw new InvalidArgumentException('Retention-Frist ist ungültig.');$amount=(int)$matches[1];$valid=match($matches[2]){'Y'=>$amount<=10,'M'=>$amount<=120,'D'=>$amount<=3650};if(!$valid)throw new InvalidArgumentException('Retention-Frist ist ungültig.');return$period;}
    private function validateExpectedRevision(mixed $revision): int {if(!is_int($revision)||$revision<0)throw new InvalidArgumentException('Policyversion ist ungültig.');return$revision;}
    private function assertRevision(array $history,int $expected): void {$actual=$history===[]?0:(int)$history[array_key_last($history)]['revision'];if($actual!==$expected)throw new DomainException('Policy wurde zwischenzeitlich geändert.');}
    private function persist(array $history): void {$this->config->setValueArray(Application::APP_ID,self::KEY,$history,true);}
}
