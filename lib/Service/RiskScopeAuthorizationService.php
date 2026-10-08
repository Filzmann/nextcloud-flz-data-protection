<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Service;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use OCA\FlzDataProtection\Db\RiskScopeAuthorizationRepositoryInterface;
use OCA\FlzDataProtection\PublicApi\V1\ScopeAuthorizationQueryEvent;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IGroupManager;
use OCP\IUserSession;
use Throwable;

/** Owns the private instance policy behind the public boolean V1 decision. */
final class RiskScopeAuthorizationService {
    public const CONFIGURATOR_GROUP = 'Datenschutzbeauftragte';

    /** @var array<string, string> scope => sole consumer app */
    private const SUPPORTED_SCOPES = [
        ScopeAuthorizationQueryEvent::FLZROOM_SECRETARIAT_FOREIGN_BOOKING_INTERVENTION => 'flzroom',
    ];

    public function __construct(
        private RiskScopeAuthorizationRepositoryInterface $repository,
        private IGroupManager $groups,
        private IUserSession $session,
        private ITimeFactory $clock,
    ) {
    }

    public function canConfigure(): bool {
        $uid = $this->session->getUser()?->getUID() ?? '';
        if ($uid === '') {
            return false;
        }
        try {
            return $this->groups->isInGroup($uid, self::CONFIGURATOR_GROUP);
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array<string, mixed> */
    public function status(): array {
        $scopes = [];
        foreach (self::SUPPORTED_SCOPES as $scopeId => $consumerAppId) {
            [$state, $configuration] = $this->state($scopeId);
            $scopes[$scopeId] = [
                'consumerAppId' => $consumerAppId,
                'authorized' => $state === 'authorized',
                'status' => $state,
                'configuration' => $configuration === null ? null : $this->serialize($configuration),
            ];
        }

        return [
            'contractVersion' => ScopeAuthorizationQueryEvent::CONTRACT_VERSION,
            'performanceMonitoringProhibited' => true,
            'scopes' => $scopes,
        ];
    }

    /** @param array<string, mixed> $input */
    public function save(array $input): array {
        if (!$this->canConfigure()) {
            throw new DomainException('Zugriff verweigert.');
        }

        $scopeId = $this->requiredString($input, 'scopeId', 160);
        if (!array_key_exists($scopeId, self::SUPPORTED_SCOPES)) {
            throw new InvalidArgumentException('Unbekannter Risikoscope.');
        }
        $enabled = $input['enabled'] ?? null;
        if (!is_bool($enabled)) {
            throw new InvalidArgumentException('Der Aktivierungsstatus ist ungültig.');
        }
        if (($input['dpoConfirmed'] ?? null) !== true) {
            throw new InvalidArgumentException('Die dokumentierte DPO-Bestätigung fehlt.');
        }
        if (array_key_exists('performanceMonitoringProhibited', $input)
            && $input['performanceMonitoringProhibited'] !== true) {
            throw new InvalidArgumentException('Das Verbot der Leistungs- und Verhaltenskontrolle ist nicht konfigurierbar.');
        }

        $effectiveAt = $this->date($input, 'effectiveAt');
        $expiresAt = $this->date($input, 'expiresAt');
        if ($expiresAt <= $effectiveAt || $expiresAt <= $this->clock->now()) {
            throw new InvalidArgumentException('Das Scope-Ende muss nach Wirksamkeit und aktuellem Zeitpunkt liegen.');
        }

        $latest = $this->repository->latest($scopeId);
        if ($latest !== null && !$this->validConfiguration($latest, $scopeId)) {
            throw new DomainException('Die gespeicherte Scope-Konfiguration ist inkompatibel.');
        }
        $actualRevision = $latest === null ? 0 : (int)($latest['revision'] ?? 0);
        $expectedRevision = $this->integer($input, 'expectedRevision');
        if ($expectedRevision !== $actualRevision) {
            throw new DomainException('Die Scope-Konfiguration wurde zwischenzeitlich geändert.');
        }

        $this->repository->appendIfCurrent([
            'schemaVersion' => ScopeAuthorizationQueryEvent::CONTRACT_VERSION,
            'scopeId' => $scopeId,
            'enabled' => $enabled,
            'policyRevision' => $this->reference($input, 'policyRevision', 64),
            'authorizationReference' => $this->reference($input, 'authorizationReference', 255),
            'effectiveAt' => $effectiveAt->format(DATE_ATOM),
            'expiresAt' => $expiresAt->format(DATE_ATOM),
            'dpoConfirmed' => true,
            'createdAt' => $this->clock->now()->format(DATE_ATOM),
        ], $expectedRevision);

        return $this->status();
    }

    public function isAuthorized(string $consumerAppId, string $scopeId, string $contractVersion): bool {
        if ($contractVersion !== ScopeAuthorizationQueryEvent::CONTRACT_VERSION
            || (self::SUPPORTED_SCOPES[$scopeId] ?? null) !== $consumerAppId) {
            return false;
        }
        try {
            return $this->state($scopeId)[0] === 'authorized';
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array{0: string, 1: array<string, mixed>|null} */
    private function state(string $scopeId): array {
        try {
            $configuration = $this->repository->latest($scopeId);
            if ($configuration === null) {
                return ['configuration_missing', null];
            }
            if (!$this->validConfiguration($configuration, $scopeId)) {
                return ['configuration_invalid', $configuration];
            }
            if ($configuration['enabled'] !== true) {
                return ['disabled', $configuration];
            }
            $now = $this->clock->now();
            $effectiveAt = new DateTimeImmutable($configuration['effectiveAt']);
            $expiresAt = new DateTimeImmutable($configuration['expiresAt']);
            if ($effectiveAt > $now) {
                return ['not_yet_effective', $configuration];
            }
            if ($expiresAt <= $now) {
                return ['expired', $configuration];
            }
            return ['authorized', $configuration];
        } catch (Throwable) {
            return ['configuration_invalid', null];
        }
    }

    /** @param array<string, mixed> $configuration */
    private function validConfiguration(array $configuration, string $scopeId): bool {
        if (($configuration['schemaVersion'] ?? null) !== ScopeAuthorizationQueryEvent::CONTRACT_VERSION
            || ($configuration['scopeId'] ?? null) !== $scopeId
            || !is_int($configuration['revision'] ?? null)
            || ($configuration['revision'] ?? 0) < 1
            || !is_bool($configuration['enabled'] ?? null)
            || ($configuration['dpoConfirmed'] ?? null) !== true) {
            return false;
        }
        foreach (['policyRevision', 'authorizationReference', 'effectiveAt', 'expiresAt', 'createdAt'] as $field) {
            if (!is_string($configuration[$field] ?? null) || trim($configuration[$field]) === '') {
                return false;
            }
        }
        try {
            $effectiveAt = new DateTimeImmutable($configuration['effectiveAt']);
            $expiresAt = new DateTimeImmutable($configuration['expiresAt']);
            new DateTimeImmutable($configuration['createdAt']);
            return $expiresAt > $effectiveAt;
        } catch (Throwable) {
            return false;
        }
    }

    /** @param array<string, mixed> $configuration */
    private function serialize(array $configuration): array {
        return $configuration;
    }

    /** @param array<string, mixed> $input */
    private function requiredString(array $input, string $field, int $maxLength): string {
        $value = $input[$field] ?? null;
        if (!is_string($value)) {
            throw new InvalidArgumentException('Ungültiges Textfeld: ' . $field);
        }
        $value = trim($value);
        if ($value === '' || strlen($value) > $maxLength) {
            throw new InvalidArgumentException('Pflichtwert fehlt oder ist zu lang: ' . $field);
        }
        return $value;
    }

    /** @param array<string, mixed> $input */
    private function reference(array $input, string $field, int $maxLength): string {
        $value = $this->requiredString($input, $field, $maxLength);
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:\/-]*$/D', $value) !== 1) {
            throw new InvalidArgumentException('Referenzen müssen opake, personenfreie Kennungen sein: ' . $field);
        }
        return $value;
    }

    /** @param array<string, mixed> $input */
    private function integer(array $input, string $field): int {
        $value = $input[$field] ?? null;
        if (!is_int($value)) {
            throw new InvalidArgumentException('Ungültiger Ganzzahlwert: ' . $field);
        }
        return $value;
    }

    /** @param array<string, mixed> $input */
    private function date(array $input, string $field): DateTimeImmutable {
        $value = $input[$field] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException('Zeitangabe fehlt: ' . $field);
        }
        try {
            return new DateTimeImmutable($value);
        } catch (Throwable $error) {
            throw new InvalidArgumentException('Zeitangabe ist ungültig: ' . $field, 0, $error);
        }
    }
}
