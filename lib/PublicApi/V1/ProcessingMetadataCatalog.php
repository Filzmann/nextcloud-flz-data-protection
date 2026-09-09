<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\PublicApi\V1;

use InvalidArgumentException;

final class ProcessingMetadataCatalog {
    private const PROCESSING_FIELDS = [
        'processing_id', 'name', 'controller', 'data_categories', 'data_subjects',
        'purposes', 'access_roles', 'recipients', 'data_sources', 'legal_basis',
        'retention', 'logging', 'backup', 'exports_and_reports',
        'data_subject_rights', 'international_transfers', 'automated_decisions',
        'special_safeguards', 'systems',
    ];

    /** @param array{schema_version: string, app_id: string, processings: list<array<string, mixed>>} $payload */
    private function __construct(private array $payload) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self {
        self::assertExactFields($payload, ['schema_version', 'app_id', 'processings'], 'catalog');
        if (($payload['schema_version'] ?? null) !== '1.0') {
            throw new InvalidArgumentException('Unsupported processing metadata schema version.');
        }
        self::assertTechnicalId($payload['app_id'] ?? null, 'app ID');
        $processings = $payload['processings'] ?? null;
        if (!is_array($processings) || $processings === [] || !array_is_list($processings)) {
            throw new InvalidArgumentException('Processing metadata catalog must contain a processing list.');
        }

        $ids = [];
        foreach ($processings as $processing) {
            if (!is_array($processing)) {
                throw new InvalidArgumentException('Invalid processing metadata entry.');
            }
            self::assertProcessing($processing);
            $processingId = $processing['processing_id'];
            if (isset($ids[$processingId])) {
                throw new InvalidArgumentException('Duplicate processing ID.');
            }
            $ids[$processingId] = true;
        }

        /** @var array{schema_version: string, app_id: string, processings: list<array<string, mixed>>} $payload */
        return new self($payload);
    }

    public function schemaVersion(): string { return $this->payload['schema_version']; }
    public function appId(): string { return $this->payload['app_id']; }

    /** @return list<string> */
    public function processingIds(): array {
        return array_map(
            static fn(array $processing): string => $processing['processing_id'],
            $this->payload['processings'],
        );
    }

    /** @return array{schema_version: string, app_id: string, processings: list<array<string, mixed>>} */
    public function toArray(): array { return $this->payload; }

    /** @param array<string, mixed> $processing */
    private static function assertProcessing(array $processing): void {
        self::assertExactFields($processing, self::PROCESSING_FIELDS, 'processing');
        self::assertTechnicalId($processing['processing_id'] ?? null, 'processing ID');
        self::assertText($processing['name'] ?? null, 'processing name');

        $controller = $processing['controller'] ?? null;
        if (!is_array($controller)) throw new InvalidArgumentException('Invalid processing controller.');
        self::assertExactFields($controller, ['component', 'business_owner'], 'controller');
        self::assertText($controller['component'] ?? null, 'controller component');
        self::assertDeclaredText($controller['business_owner'] ?? null, 'business owner');

        foreach (['data_categories', 'data_subjects', 'purposes', 'access_roles', 'data_sources', 'legal_basis', 'special_safeguards', 'systems'] as $field) {
            self::assertDeclaredList($processing[$field] ?? null, $field);
        }
        foreach (['international_transfers', 'automated_decisions'] as $field) {
            self::assertDeclaredText($processing[$field] ?? null, $field);
        }
        self::assertRecipients($processing['recipients'] ?? null);
        self::assertRetention($processing['retention'] ?? null);
        self::assertLogging($processing['logging'] ?? null);
        self::assertBackup($processing['backup'] ?? null);
        self::assertExports($processing['exports_and_reports'] ?? null);
        self::assertRights($processing['data_subject_rights'] ?? null);
    }

    private static function assertRecipients(mixed $value): void {
        if (self::isDecisionRequired($value)) return;
        if (!is_array($value) || $value === [] || !array_is_list($value)) {
            throw new InvalidArgumentException('Invalid recipients.');
        }
        foreach ($value as $recipient) {
            if (!is_array($recipient)) throw new InvalidArgumentException('Invalid recipient.');
            $allowed = ['category', 'allowed_disclosures'];
            if (array_key_exists('conditions', $recipient)) $allowed[] = 'conditions';
            self::assertExactFields($recipient, $allowed, 'recipient');
            self::assertText($recipient['category'] ?? null, 'recipient category');
            self::assertDeclaredList($recipient['allowed_disclosures'] ?? null, 'allowed disclosures');
            if (array_key_exists('conditions', $recipient)) self::assertTextList($recipient['conditions'], 'recipient conditions');
        }
    }

    private static function assertRetention(mixed $value): void {
        if (self::isDecisionRequired($value)) return;
        if (!is_array($value)) throw new InvalidArgumentException('Invalid retention rule.');
        $allowed = ['policy_id', 'trigger', 'duration_or_deadline', 'action'];
        if (array_key_exists('conditions_or_holds', $value)) $allowed[] = 'conditions_or_holds';
        self::assertExactFields($value, $allowed, 'retention rule');
        self::assertTechnicalId($value['policy_id'] ?? null, 'retention policy ID');
        self::assertText($value['trigger'] ?? null, 'retention trigger');
        self::assertText($value['duration_or_deadline'] ?? null, 'retention duration');
        if (!in_array($value['action'] ?? null, ['delete', 'anonymize', 'restrict', 'review'], true)) {
            throw new InvalidArgumentException('Invalid retention action.');
        }
        if (array_key_exists('conditions_or_holds', $value)) self::assertTextList($value['conditions_or_holds'], 'retention conditions');
    }

    private static function assertLogging(mixed $value): void {
        if (!is_array($value)) throw new InvalidArgumentException('Invalid logging metadata.');
        self::assertExactFields($value, ['audit_requirements', 'personal_data_policy'], 'logging');
        self::assertDeclaredList($value['audit_requirements'] ?? null, 'audit requirements');
        self::assertDeclaredText($value['personal_data_policy'] ?? null, 'logging personal data policy');
    }

    private static function assertBackup(mixed $value): void {
        if (!is_array($value)) throw new InvalidArgumentException('Invalid backup metadata.');
        self::assertExactFields($value, ['relevance', 'restore_handling'], 'backup');
        $relevance = $value['relevance'] ?? null;
        if (!in_array($relevance, ['included', 'excluded', 'not_applicable'], true) && !self::isDecisionRequired($relevance)) {
            throw new InvalidArgumentException('Invalid backup relevance.');
        }
        self::assertDeclaredText($value['restore_handling'] ?? null, 'backup restore handling');
    }

    private static function assertExports(mixed $value): void {
        if (!is_array($value)) throw new InvalidArgumentException('Invalid export metadata.');
        self::assertExactFields($value, ['article_15_relevance', 'other_outputs', 'minimization'], 'exports and reports');
        self::assertDeclaredText($value['article_15_relevance'] ?? null, 'Article 15 relevance');
        self::assertDeclaredList($value['other_outputs'] ?? null, 'other outputs');
        self::assertDeclaredText($value['minimization'] ?? null, 'output minimization');
    }

    private static function assertRights(mixed $value): void {
        if (!is_array($value)) throw new InvalidArgumentException('Invalid data subject rights metadata.');
        self::assertExactFields($value, ['article_15', 'rectification', 'restriction', 'erasure_or_anonymization'], 'data subject rights');
        foreach (['article_15', 'rectification', 'restriction', 'erasure_or_anonymization'] as $field) {
            self::assertDeclaredText($value[$field] ?? null, $field);
        }
    }

    private static function assertDeclaredText(mixed $value, string $field): void {
        if (self::isDecisionRequired($value)) return;
        self::assertText($value, $field);
    }

    private static function assertDeclaredList(mixed $value, string $field): void {
        if (self::isDecisionRequired($value)) return;
        self::assertTextList($value, $field);
    }

    private static function isDecisionRequired(mixed $value): bool {
        if (!is_array($value) || ($value['status'] ?? null) !== 'PRIVACY-DECISION-REQUIRED') return false;
        $fields = ['status', 'affected_data', 'planned_processing', 'missing_decision', 'reason', 'technical_impact', 'privacy_preserving_alternative', 'responsible_party', 'blocking'];
        self::assertExactFields($value, $fields, 'privacy decision');
        foreach (array_diff($fields, ['status', 'blocking']) as $field) self::assertText($value[$field] ?? null, $field);
        if (!is_bool($value['blocking'] ?? null)) throw new InvalidArgumentException('Invalid privacy decision blocking state.');
        return true;
    }

    private static function assertTextList(mixed $value, string $field): void {
        if (!is_array($value) || $value === [] || !array_is_list($value) || count($value) !== count(array_unique($value, SORT_REGULAR))) {
            throw new InvalidArgumentException("Invalid {$field} list.");
        }
        foreach ($value as $entry) self::assertText($entry, $field);
    }

    private static function assertText(mixed $value, string $field): void {
        if (!is_string($value) || trim($value) === '') throw new InvalidArgumentException("Invalid {$field}.");
    }

    private static function assertTechnicalId(mixed $value, string $field): void {
        if (!is_string($value) || !preg_match('/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/', $value)) {
            throw new InvalidArgumentException("Invalid {$field}.");
        }
    }

    /** @param array<string, mixed> $value @param list<string> $fields */
    private static function assertExactFields(array $value, array $fields, string $context): void {
        $actual = array_keys($value);
        sort($actual);
        sort($fields);
        if ($actual !== $fields) throw new InvalidArgumentException("Invalid {$context} fields.");
    }
}
