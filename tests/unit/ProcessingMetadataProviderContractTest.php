<?php

declare(strict_types=1);

use OCA\FlzDataProtection\Privacy\DataProtectionProcessingMetadataProvider;
use OCA\FlzDataProtection\Privacy\DataProtectionProcessingMetadataProviderListener;
use OCA\FlzDataProtection\PublicApi\V1\ProcessingMetadataCatalog;
use OCA\FlzDataProtection\PublicApi\V1\ProcessingMetadataProvider;
use OCA\FlzDataProtection\PublicApi\V1\ProcessingMetadataProviderDescriptor;
use OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
use OCA\FlzDataProtection\PublicApi\V1\Testing\ProcessingMetadataProviderContractTestKit;
use OCA\FlzDataProtection\Service\ProcessingMetadataProviderRegistry;

$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Erwartet: ' . var_export($expected, true) . '; erhalten: ' . var_export($actual, true));
    }
};

$decisionRequired = [
    'status' => 'PRIVACY-DECISION-REQUIRED',
    'affected_data' => 'Synthetic record',
    'planned_processing' => 'Synthetic processing',
    'missing_decision' => 'Retention period',
    'reason' => 'Required before an automatic action.',
    'technical_impact' => 'No automatic action is available.',
    'privacy_preserving_alternative' => 'Keep the action disabled.',
    'responsible_party' => 'ungeklärt',
    'blocking' => true,
];
$processing = [
    'processing_id' => 'synthetic_processing',
    'name' => 'Synthetic processing',
    'controller' => ['component' => 'Reference component', 'business_owner' => $decisionRequired],
    'data_categories' => ['Synthetic identifiers'],
    'data_subjects' => ['Synthetic users'],
    'purposes' => ['Contract verification'],
    'access_roles' => ['Authenticated synthetic user'],
    'recipients' => [[
        'category' => 'Synthetic data subject',
        'allowed_disclosures' => ['Own synthetic record'],
    ]],
    'data_sources' => ['Synthetic provider'],
    'legal_basis' => $decisionRequired,
    'retention' => $decisionRequired,
    'logging' => [
        'audit_requirements' => ['Technical status only'],
        'personal_data_policy' => 'Do not log the synthetic record.',
    ],
    'backup' => [
        'relevance' => 'excluded',
        'restore_handling' => 'Not applicable.',
    ],
    'exports_and_reports' => [
        'article_15_relevance' => 'Included',
        'other_outputs' => ['None'],
        'minimization' => 'Only the defined fields are exposed.',
    ],
    'data_subject_rights' => [
        'article_15' => 'Supported',
        'rectification' => 'Manual review',
        'restriction' => 'Manual review',
        'erasure_or_anonymization' => 'Manual review',
    ],
    'international_transfers' => 'None',
    'automated_decisions' => 'None',
    'special_safeguards' => ['Synthetic data only'],
    'systems' => ['Reference Nextcloud app'],
];
$payload = [
    'schema_version' => '1.0',
    'app_id' => 'reference_app',
    'processings' => [$processing],
];

$catalog = ProcessingMetadataCatalog::fromArray($payload);
$assertSame('1.0', $catalog->schemaVersion(), 'Schema-Version ist nicht lesbar.');
$assertSame('reference_app', $catalog->appId(), 'Katalog-App-ID ist nicht lesbar.');
$assertSame(['synthetic_processing'], $catalog->processingIds(), 'Processing-IDs sind nicht stabil lesbar.');
$assertSame($decisionRequired, $catalog->toArray()['processings'][0]['retention'], 'Eine fachliche Entscheidungslücke wurde verändert oder verborgen.');

$expectInvalid = static function (array $invalidPayload, string $message): void {
    try {
        ProcessingMetadataCatalog::fromArray($invalidPayload);
    } catch (InvalidArgumentException) {
        return;
    }
    throw new RuntimeException($message);
};

$duplicate = $payload;
$duplicate['processings'][] = $processing;
$expectInvalid($duplicate, 'Eine doppelte processing_id wurde akzeptiert.');

foreach (require dirname(__DIR__) . '/fixtures/processing-metadata-recipients.php' as $name => $case) {
    if ($case['valid']) {
        $assertSame($case['payload'], ProcessingMetadataCatalog::fromArray($case['payload'])->toArray(), 'Gültige Empfänger wurden verändert: ' . $name);
    } else {
        $expectInvalid($case['payload'], 'Schemawidrige Empfänger wurden akzeptiert: ' . $name);
    }
}

$runtimeData = $payload;
$runtimeData['personal_runtime_data'] = [['uid' => 'synthetic-user']];
$expectInvalid($runtimeData, 'Personenbezogene Laufzeitdaten wurden im Metadatenkatalog akzeptiert.');

$missingPurpose = $payload;
unset($missingPurpose['processings'][0]['purposes']);
$expectInvalid($missingPurpose, 'Ein Processing ohne Zweckfeld wurde akzeptiert.');

$unknownField = $payload;
$unknownField['processings'][0]['future_data_dump'] = true;
$expectInvalid($unknownField, 'Ein unbekanntes Processing-Feld wurde akzeptiert.');

$provider = new class($catalog) implements ProcessingMetadataProvider {
    public function __construct(private ProcessingMetadataCatalog $catalog) {}
    public function descriptor(): ProcessingMetadataProviderDescriptor {
        return new ProcessingMetadataProviderDescriptor('reference_app', 'Reference app', '1.0');
    }
    public function catalog(): ProcessingMetadataCatalog { return $this->catalog; }
};
$verified = ProcessingMetadataProviderContractTestKit::verify($provider);
$assertSame(['synthetic_processing'], $verified->processingIds(), 'Das Contract-Test-Kit liefert nicht den geprüften Katalog.');

$mismatchProvider = new class($catalog) implements ProcessingMetadataProvider {
    public function __construct(private ProcessingMetadataCatalog $catalog) {}
    public function descriptor(): ProcessingMetadataProviderDescriptor {
        return new ProcessingMetadataProviderDescriptor('other_app', 'Other app', '1.0');
    }
    public function catalog(): ProcessingMetadataCatalog { return $this->catalog; }
};
$registry = new ProcessingMetadataProviderRegistry();
$mismatchRejected = false;
try {
    $registry->register($mismatchProvider);
} catch (DomainException) {
    $mismatchRejected = true;
}
$assertSame(true, $mismatchRejected, 'Ein Katalog einer fremden App wurde registriert.');

$event = new RegisterProcessingMetadataProvidersEvent();
$event->register($provider);
$event->register($provider);
$assertSame([], $event->providers(), 'Eine doppelte Provider-ID blieb ausführbar.');
$assertSame(['reference_app' => 'Processing metadata provider incompatible.'], $event->registrationFailures(), 'Doppelte Registrierung ist nicht diagnostizierbar.');

$failingProvider = new class implements ProcessingMetadataProvider {
    public function descriptor(): ProcessingMetadataProviderDescriptor {
        return new ProcessingMetadataProviderDescriptor('failing_app', 'Failing app', '1.0');
    }
    public function catalog(): ProcessingMetadataCatalog {
        throw new RuntimeException('Synthetic catalog detail must not escape.');
    }
};
$isolatedEvent = new RegisterProcessingMetadataProvidersEvent();
$isolatedEvent->register($failingProvider);
$isolatedEvent->register($provider);
$assertSame(['reference_app'], array_keys($isolatedEvent->providers()), 'Ein defekter Katalog blockiert einen nachfolgenden Provider.');
$assertSame(
    ['failing_app' => 'Processing metadata provider incompatible.'],
    $isolatedEvent->registrationFailures(),
    'Ein defekter Katalog bleibt nicht datensparsam diagnostizierbar.',
);

$failingDescriptorProvider = new class implements ProcessingMetadataProvider {
    public function descriptor(): ProcessingMetadataProviderDescriptor {
        throw new RuntimeException('Synthetic descriptor detail must not escape.');
    }
    public function catalog(): ProcessingMetadataCatalog {
        throw new RuntimeException('Catalog must not be called after a descriptor failure.');
    }
};
$descriptorEvent = new RegisterProcessingMetadataProvidersEvent();
$descriptorEvent->register($failingDescriptorProvider);
$descriptorEvent->register($provider);
$assertSame(['reference_app'], array_keys($descriptorEvent->providers()), 'Ein defekter Descriptor blockiert einen nachfolgenden Provider.');
$assertSame(
    ['unknown_provider_1' => 'Processing metadata provider incompatible.'],
    $descriptorEvent->registrationFailures(),
    'Ein defekter Descriptor bleibt nicht datensparsam diagnostizierbar.',
);

$realProvider = new DataProtectionProcessingMetadataProvider();
$realCatalog = ProcessingMetadataProviderContractTestKit::verify($realProvider);
$assertSame('flz_data_protection', $realCatalog->appId(), 'Der eigene Katalog verwendet nicht die kanonische App-ID.');
$assertSame(
    ['article_15_aggregation', 'temporary_admin_full_access', 'retention_execution_profile_configuration', 'retention_execution_technical_activation'],
    $realCatalog->processingIds(),
    'Der eigene Katalog beschreibt nicht den freigegebenen Pilotumfang.',
);
$realProcessings = $realCatalog->toArray()['processings'];
$profileProcessing = $realProcessings[2] ?? null;
$assertSame('PRIVACY-DECISION-REQUIRED', $profileProcessing['retention']['status'] ?? null, 'Die Profilrevisionen wurden fälschlich unter die Löschfrist der Adminfreigabehistorie gestellt.');
$activationProcessing = $realProcessings[3] ?? null;
$assertSame(false, $activationProcessing['legal_basis']['blocking'] ?? null, 'Offene kundenlokale Rechtsdokumentation blockiert die technische Aktivierung weiterhin.');
$assertSame('P24M', $activationProcessing['retention']['duration_or_deadline'] ?? null, 'Die beschlossene 24-Monatsfrist der Aktivierungsrevisionen fehlt.');
$realEvent = new RegisterProcessingMetadataProvidersEvent();
(new DataProtectionProcessingMetadataProviderListener($realProvider))->handle($realEvent);
$assertSame($realProvider, $realEvent->providers()['flz_data_protection'] ?? null, 'Der eigene Processing-Metadata-Provider wird nicht lazy registriert.');

echo "Processing metadata provider contract tests passed.\n";
