<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Service;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use OCA\FilzmannDataProtection\Db\RetentionExecutionProfileRepositoryInterface;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IGroupManager;
use OCP\IUserSession;
use Throwable;

final class RetentionExecutionProfileService {
    public const CONFIGURATOR_GROUP = 'Datenschutzbeauftragte';
    public const COLLECTIVE_AGREEMENT = 'employment_collective_agreement_de';
    public const LEGITIMATE_INTEREST = 'legitimate_interest_it_security_de';

    public function __construct(
        private RetentionExecutionProfileRepositoryInterface $repository,
        private IGroupManager $groups,
        private IUserSession $session,
        private ITimeFactory $clock,
    ) {
    }

    public function canConfigure(): bool {
        $uid = $this->session->getUser()?->getUID() ?? '';
        if ($uid === '') return false;
        try {
            return $this->groups->isInGroup($uid, self::CONFIGURATOR_GROUP);
        } catch (Throwable) {
            return false;
        }
    }

    public function status(): array {
        $configuration = $this->repository->latest();
        $blockers = $configuration === null ? ['configuration_missing'] : $this->currentBlockers($configuration);

        return [
            'action' => 'REVIEW',
            'configurationValid' => $blockers === [],
            'executionAvailable' => false,
            'performanceMonitoringProhibited' => true,
            'blockers' => $blockers,
            'configuration' => $configuration === null ? null : $this->serialize($configuration),
        ];
    }

    public function history(): array {
        return array_map(fn(array $entry): array => $this->serialize($entry), $this->repository->history());
    }

    public function save(array $input): array {
        $uid = $this->requireConfigurator();
        $now = $this->clock->now();
        $latest = $this->repository->latest();
        $actualRevision = $latest === null ? 0 : (int)$latest['revision'];
        $expectedRevision = $this->integer($input, 'expectedRevision');
        if ($expectedRevision !== $actualRevision) {
            throw new DomainException('Das Kundenprofil wurde zwischenzeitlich geändert.');
        }

        $profileId = $this->oneOf($input, 'profileId', [self::COLLECTIVE_AGREEMENT, self::LEGITIMATE_INTEREST]);
        if (array_key_exists('performanceMonitoringProhibited', $input) && $input['performanceMonitoringProhibited'] !== true) {
            throw new InvalidArgumentException('Das Verbot der Leistungs- und Verhaltenskontrolle ist nicht konfigurierbar.');
        }

        $effectiveAt = $this->date($input, 'effectiveAt');
        $legalReviewDueAt = $this->date($input, 'legalReviewDueAt');
        $backupEvidenceAt = $this->date($input, 'backupEvidenceAt');
        $backupReviewDueAt = $this->date($input, 'backupReviewDueAt');
        $restoreTestedAt = $this->date($input, 'restoreTestedAt');
        if ($effectiveAt < $now) throw new InvalidArgumentException('Das Profil darf nicht rückdatiert werden.');
        if ($legalReviewDueAt <= $effectiveAt || $backupReviewDueAt <= $now || $backupReviewDueAt <= $backupEvidenceAt) throw new InvalidArgumentException('Der nächste Prüftermin muss nach Wirksamkeit beziehungsweise Nachweis liegen.');
        if ($backupEvidenceAt > $now || $restoreTestedAt > $now) throw new InvalidArgumentException('Betriebsnachweise dürfen nicht aus der Zukunft stammen.');

        $regularDays = $this->integer($input, 'backupRegularDays');
        $bufferDays = $this->integer($input, 'backupBufferDays');
        if ($regularDays < 1 || $regularDays > 30 || $bufferDays < 0 || $bufferDays > 5 || $regularDays + $bufferDays > 35) {
            throw new InvalidArgumentException('Die Backupfrist liegt außerhalb der Produktgrenze von 30+5 Tagen.');
        }
        if (($input['dpoConfirmed'] ?? null) !== true) {
            throw new InvalidArgumentException('Die dokumentierte DPO-Bestätigung fehlt.');
        }

        $allEmployees = ($input['allAccountsEmployeesConfirmed'] ?? null) === true;
        $necessity = $this->optionalString($input, 'necessityAssessmentReference', 255);
        $impact = $this->optionalString($input, 'impactAssessmentReference', 255);
        if ($profileId === self::COLLECTIVE_AGREEMENT && !$allEmployees) {
            throw new InvalidArgumentException('Das Kollektivvereinbarungsprofil gilt nur für bestätigte Beschäftigtenkonten.');
        }
        if ($profileId === self::LEGITIMATE_INTEREST && ($necessity === '' || $impact === '')) {
            throw new InvalidArgumentException('Das Interessenabwägungsprofil benötigt Erforderlichkeits- und Auswirkungsnachweis.');
        }

        $configuration = [
            'revision' => $actualRevision + 1,
            'profileId' => $profileId,
            'profileRevision' => $this->requiredString($input, 'profileRevision', 64),
            'legalEvidenceReference' => $this->requiredString($input, 'legalEvidenceReference', 255),
            'scopeReference' => $this->requiredString($input, 'scopeReference', 255),
            'purposeReference' => $this->requiredString($input, 'purposeReference', 255),
            'necessityAssessmentReference' => $necessity,
            'impactAssessmentReference' => $impact,
            'safeguardsReference' => $this->requiredString($input, 'safeguardsReference', 255),
            'accountCategories' => $this->requiredString($input, 'accountCategories', 255),
            'allAccountsEmployeesConfirmed' => $allEmployees,
            'effectiveAt' => $effectiveAt,
            'legalReviewDueAt' => $legalReviewDueAt,
            'backupRegularDays' => $regularDays,
            'backupBufferDays' => $bufferDays,
            'backupResponsibleParty' => $this->requiredString($input, 'backupResponsibleParty', 255),
            'backupScope' => $this->requiredString($input, 'backupScope', 255),
            'backupEvidenceReference' => $this->requiredString($input, 'backupEvidenceReference', 255),
            'backupEvidenceAt' => $backupEvidenceAt,
            'backupReviewDueAt' => $backupReviewDueAt,
            'restoreTestReference' => $this->requiredString($input, 'restoreTestReference', 255),
            'restoreTestedAt' => $restoreTestedAt,
            'dpoConfirmed' => true,
            'changedBy' => $uid,
            'createdAt' => $now,
        ];
        $this->repository->append($configuration);

        return $this->status();
    }

    private function currentBlockers(array $configuration): array {
        $now = $this->clock->now();
        $blockers = [];
        try {
            $profileId = $configuration['profileId'] ?? null;
            if (!in_array($profileId, [self::COLLECTIVE_AGREEMENT, self::LEGITIMATE_INTEREST], true)) $blockers[] = 'profile_unknown';
            foreach (['profileRevision', 'legalEvidenceReference', 'scopeReference', 'purposeReference', 'safeguardsReference', 'accountCategories', 'backupResponsibleParty', 'backupScope', 'backupEvidenceReference', 'restoreTestReference', 'changedBy'] as $field) {
                if (!is_string($configuration[$field] ?? null) || trim($configuration[$field]) === '') $blockers[] = 'required_evidence_missing';
            }
            if ($profileId === self::COLLECTIVE_AGREEMENT && ($configuration['allAccountsEmployeesConfirmed'] ?? false) !== true) $blockers[] = 'employee_scope_unconfirmed';
            if ($profileId === self::LEGITIMATE_INTEREST
                && ((!is_string($configuration['necessityAssessmentReference'] ?? null) || trim($configuration['necessityAssessmentReference']) === '')
                    || (!is_string($configuration['impactAssessmentReference'] ?? null) || trim($configuration['impactAssessmentReference']) === ''))) {
                $blockers[] = 'balancing_evidence_missing';
            }
            if (($configuration['dpoConfirmed'] ?? false) !== true) $blockers[] = 'dpo_confirmation_missing';
            foreach (['effectiveAt', 'legalReviewDueAt', 'backupEvidenceAt', 'backupReviewDueAt', 'restoreTestedAt', 'createdAt'] as $field) {
                if (!($configuration[$field] ?? null) instanceof DateTimeImmutable) $blockers[] = 'required_timestamp_missing';
            }
            if ($blockers !== []) return array_values(array_unique($blockers));
            if ($configuration['effectiveAt'] > $now) $blockers[] = 'profile_not_yet_effective';
            if ($configuration['effectiveAt'] < $configuration['createdAt']) $blockers[] = 'profile_effective_at_invalid';
            if ($configuration['legalReviewDueAt'] <= $now || $configuration['legalReviewDueAt'] <= $configuration['effectiveAt']) $blockers[] = 'legal_review_due';
            if ($configuration['backupEvidenceAt'] > $now) $blockers[] = 'backup_evidence_invalid';
            if ($configuration['restoreTestedAt'] > $now) $blockers[] = 'restore_evidence_invalid';
            if ($configuration['backupReviewDueAt'] <= $now || $configuration['backupReviewDueAt'] <= $configuration['backupEvidenceAt']) $blockers[] = 'backup_review_due';
            if ($configuration['createdAt'] > $now) $blockers[] = 'audit_timestamp_invalid';
            $regular = (int)($configuration['backupRegularDays'] ?? 0);
            $buffer = (int)($configuration['backupBufferDays'] ?? -1);
            if ($regular < 1 || $regular > 30 || $buffer < 0 || $buffer > 5 || $regular + $buffer > 35) $blockers[] = 'backup_limit_invalid';
        } catch (Throwable) {
            return ['configuration_invalid'];
        }
        return $blockers;
    }

    private function requireConfigurator(): string {
        $uid = $this->session->getUser()?->getUID() ?? '';
        if ($uid === '' || !$this->canConfigure()) throw new DomainException('Zugriff verweigert.');
        return $uid;
    }

    private function oneOf(array $input, string $field, array $allowed): string {
        $value = $input[$field] ?? null;
        if (!is_string($value) || !in_array($value, $allowed, true)) throw new InvalidArgumentException('Unbekanntes deutsches Rechtsgrundlagenprofil.');
        return $value;
    }

    private function requiredString(array $input, string $field, int $max): string {
        $value = $this->optionalString($input, $field, $max);
        if ($value === '') throw new InvalidArgumentException('Pflichtnachweis fehlt: ' . $field);
        return $value;
    }

    private function optionalString(array $input, string $field, int $max): string {
        $value = $input[$field] ?? '';
        if (!is_string($value)) throw new InvalidArgumentException('Ungültiges Textfeld: ' . $field);
        $value = trim($value);
        if (strlen($value) > $max) throw new InvalidArgumentException('Textfeld ist zu lang: ' . $field);
        return $value;
    }

    private function integer(array $input, string $field): int {
        $value = $input[$field] ?? null;
        if (!is_int($value)) throw new InvalidArgumentException('Ungültiger Ganzzahlwert: ' . $field);
        return $value;
    }

    private function date(array $input, string $field): DateTimeImmutable {
        $value = $input[$field] ?? null;
        if (!is_string($value) || trim($value) === '') throw new InvalidArgumentException('Zeitangabe fehlt: ' . $field);
        try {
            return new DateTimeImmutable($value);
        } catch (Throwable $error) {
            throw new InvalidArgumentException('Zeitangabe ist ungültig: ' . $field, 0, $error);
        }
    }

    private function serialize(array $configuration): array {
        foreach (['effectiveAt', 'legalReviewDueAt', 'backupEvidenceAt', 'backupReviewDueAt', 'restoreTestedAt', 'createdAt'] as $field) {
            if (($configuration[$field] ?? null) instanceof DateTimeImmutable) {
                $configuration[$field] = $configuration[$field]->format(DATE_ATOM);
            }
        }
        return $configuration;
    }
}
