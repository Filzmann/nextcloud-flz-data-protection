<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Service;

use DateInterval;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use OCA\FlzDataProtection\Db\RetentionExecutionActivationRepositoryInterface;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IGroupManager;
use OCP\IUserSession;
use Throwable;

/** Technical operator gate; customer-local legal and participation decisions stay outside the product. */
final class RetentionExecutionActivationService implements RetentionExecutionProfileStatus {
    private const RECOMMENDED_POLICY_IDS = [
        'flzroom:room_booking_delete',
        'flzroom:temporary_admin_access_history_delete',
        'flz_data_protection:temporary_admin_access_history_delete',
    ];

    public function __construct(
        private RetentionExecutionActivationRepositoryInterface $repository,
        private IGroupManager $groups,
        private IUserSession $session,
        private ITimeFactory $clock,
    ) {}

    public function canConfigure(): bool {
        $uid = $this->session->getUser()?->getUID() ?? '';
        if ($uid === '') return false;
        try {
            return $this->groups->isAdmin($uid);
        } catch (Throwable) {
            return false;
        }
    }

    public function status(): array {
        $configuration = $this->repository->latest();
        $blockers = $configuration === null ? ['activation_missing'] : $this->currentBlockers($configuration);
        $executionAvailable = $configuration !== null
            && ($configuration['enabled'] ?? false) === true
            && $blockers === [];

        return [
            'action' => $executionAvailable ? 'DELETE' : 'REVIEW',
            'setupRequired' => $configuration === null,
            'configurationValid' => $configuration !== null && $blockers === [],
            'executionAvailable' => $executionAvailable,
            'performanceMonitoringProhibited' => true,
            'blockers' => $blockers,
            'recommendedPolicyIds' => self::RECOMMENDED_POLICY_IDS,
            'configuration' => $configuration === null ? null : $this->serialize($configuration),
        ];
    }

    public function history(): array {
        return array_map(fn(array $row): array => $this->serialize($row), $this->repository->history());
    }

    public function save(array $input): array {
        $uid = $this->requireOperator();
        $expectedRevision = $this->integer($input, 'expectedRevision');
        if ($expectedRevision < 0) throw new InvalidArgumentException('Die erwartete Revision ist ungültig.');
        $enabled = $input['enabled'] ?? null;
        if (!is_bool($enabled)) throw new InvalidArgumentException('Der Aktivierungsstatus ist ungültig.');

        $now = $this->clock->now();
        $configuration = [
            'enabled' => $enabled,
            'approvedPolicyIds' => self::RECOMMENDED_POLICY_IDS,
            'backupRegularDays' => 365,
            'backupBufferDays' => 5,
            'backupVerifiedAt' => null,
            'restoreVerifiedAt' => null,
            'verificationDueAt' => null,
            'changedBy' => $uid,
            'createdAt' => $now,
        ];

        if ($enabled) {
            $regularDays = $this->integer($input, 'backupRegularDays');
            $bufferDays = $this->integer($input, 'backupBufferDays');
            if ($regularDays < 1 || $regularDays > 365 || $bufferDays < 0 || $bufferDays > 5 || $regularDays + $bufferDays > 370) {
                throw new InvalidArgumentException('Die technische Backupgrenze liegt außerhalb von 365+5 Tagen.');
            }
            $backupVerifiedAt = $this->date($input, 'backupVerifiedAt');
            $restoreVerifiedAt = $this->date($input, 'restoreVerifiedAt');
            $verificationDueAt = $this->date($input, 'verificationDueAt');
            if ($backupVerifiedAt > $now || $restoreVerifiedAt > $now) {
                throw new InvalidArgumentException('Technische Prüfzeitpunkte dürfen nicht in der Zukunft liegen.');
            }
            if ($verificationDueAt <= $now
                || $verificationDueAt <= $backupVerifiedAt
                || $verificationDueAt <= $restoreVerifiedAt
                || $verificationDueAt > $now->add(new DateInterval('P1Y'))) {
                throw new InvalidArgumentException('Die nächste technische Prüfung muss zukünftig und spätestens in einem Jahr liegen.');
            }
            $configuration = [
                ...$configuration,
                'backupRegularDays' => $regularDays,
                'backupBufferDays' => $bufferDays,
                'backupVerifiedAt' => $backupVerifiedAt,
                'restoreVerifiedAt' => $restoreVerifiedAt,
                'verificationDueAt' => $verificationDueAt,
            ];
        }

        $this->repository->appendIfCurrent($configuration, $expectedRevision);
        return $this->status();
    }

    private function currentBlockers(array $configuration): array {
        try {
            if (!is_bool($configuration['enabled'] ?? null)) return ['activation_invalid'];
            if (($configuration['enabled'] ?? false) !== true) return [];
            if (($configuration['approvedPolicyIds'] ?? null) !== self::RECOMMENDED_POLICY_IDS) return ['policy_scope_invalid'];
            foreach (['backupVerifiedAt', 'restoreVerifiedAt', 'verificationDueAt', 'createdAt'] as $field) {
                if (!($configuration[$field] ?? null) instanceof DateTimeImmutable) return ['technical_verification_invalid'];
            }
            $now = $this->clock->now();
            if ($configuration['backupVerifiedAt'] > $now || $configuration['restoreVerifiedAt'] > $now || $configuration['createdAt'] > $now) {
                return ['technical_verification_invalid'];
            }
            if ($configuration['verificationDueAt'] <= $now) return ['technical_verification_due'];
            if ($configuration['verificationDueAt'] <= $configuration['backupVerifiedAt']
                || $configuration['verificationDueAt'] <= $configuration['restoreVerifiedAt']
                || $configuration['verificationDueAt'] > $configuration['createdAt']->add(new DateInterval('P1Y'))) {
                return ['technical_verification_invalid'];
            }
            $regularDays = $configuration['backupRegularDays'] ?? null;
            $bufferDays = $configuration['backupBufferDays'] ?? null;
            if (!is_int($regularDays) || !is_int($bufferDays)
                || $regularDays < 1 || $regularDays > 365
                || $bufferDays < 0 || $bufferDays > 5
                || $regularDays + $bufferDays > 370) {
                return ['backup_limit_invalid'];
            }
            if (!is_string($configuration['changedBy'] ?? null) || trim($configuration['changedBy']) === '') {
                return ['activation_invalid'];
            }
        } catch (Throwable) {
            return ['activation_invalid'];
        }
        return [];
    }

    private function requireOperator(): string {
        $uid = $this->session->getUser()?->getUID() ?? '';
        if ($uid === '' || !$this->canConfigure()) throw new DomainException('Zugriff verweigert.');
        return $uid;
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
        foreach (['backupVerifiedAt', 'restoreVerifiedAt', 'verificationDueAt', 'createdAt'] as $field) {
            if (($configuration[$field] ?? null) instanceof DateTimeImmutable) {
                $configuration[$field] = $configuration[$field]->format(DATE_ATOM);
            }
        }
        return $configuration;
    }
}
