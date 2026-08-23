<?php

declare(strict_types=1);

use OCA\FilzmannDataProtection\PublicApi\V1\DataSubjectRef;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataEntry;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\ProviderDescriptor;
use OCA\FilzmannDataProtection\Service\PersonalDataAggregator;
use OCA\FilzmannDataProtection\Service\PersonalDataProviderRegistry;

$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Erwartet: ' . var_export($expected, true) . '; erhalten: ' . var_export($actual, true));
    }
};

$provider = static function (string $appId, bool $fails = false): PersonalDataProvider {
    return new class($appId, $fails) implements PersonalDataProvider {
        public function __construct(private string $appId, private bool $fails) {
        }

        public function descriptor(): ProviderDescriptor {
            return new ProviderDescriptor($this->appId, strtoupper($this->appId), '1.0', ['nextcloud-user'], ['personal-data'], 100);
        }

        public function collect(PersonalDataRequest $request): PersonalDataPage {
            if ($this->fails) {
                throw new RuntimeException('synthetic provider detail must not escape');
            }

            return new PersonalDataPage('complete', [new PersonalDataEntry(
                categoryId: 'profile',
                categoryLabel: 'Profile',
                summary: 'Synthetic account reference',
                purpose: 'Account administration',
                source: 'Authenticated account',
                recipientCategories: ['Instance administrators'],
                retention: 'Until the account lifecycle has been reviewed',
                thirdCountryTransfer: 'None declared by the provider',
                automatedDecision: 'None declared by the provider',
                thirdPartyContentNotice: null,
                attributes: ['account_reference' => 'synthetic-user'],
            )]);
        }
    };
};

$registry = new PersonalDataProviderRegistry();
$registry->register($provider('reference_app'));
$registry->register($provider('failing_app', true));

$duplicateRejected = false;
try {
    $registry->register($provider('reference_app'));
} catch (DomainException) {
    $duplicateRejected = true;
}
$assertSame(true, $duplicateRejected, 'Eine doppelte Provider-App-ID wurde nicht abgewiesen.');

$incompatibleRejected = false;
try {
    $registry->register(new class implements PersonalDataProvider {
        public function descriptor(): ProviderDescriptor {
            return new ProviderDescriptor('future_app', 'Future app', '2.0', ['nextcloud-user'], ['personal-data'], 100);
        }

        public function collect(PersonalDataRequest $request): PersonalDataPage {
            return new PersonalDataPage('complete');
        }
    });
} catch (DomainException) {
    $incompatibleRejected = true;
}
$assertSame(true, $incompatibleRejected, 'Eine inkompatible Vertragsversion wurde nicht abgewiesen.');

$incompleteEntryRejected = false;
try {
    new PersonalDataEntry(
        categoryId: 'profile',
        categoryLabel: 'Profile',
        summary: 'Synthetic account reference',
        purpose: '',
        source: 'Authenticated account',
        recipientCategories: ['Instance administrators'],
        retention: 'Review required',
        thirdCountryTransfer: 'None declared by the provider',
        automatedDecision: 'None declared by the provider',
        thirdPartyContentNotice: null,
        attributes: [],
    );
} catch (InvalidArgumentException) {
    $incompleteEntryRejected = true;
}
$assertSame(true, $incompleteEntryRejected, 'Ein Art.-15-Eintrag ohne Zweck wurde akzeptiert.');

$request = new PersonalDataRequest(
    new DataSubjectRef('nextcloud-user', 'synthetic-user'),
    'de',
    'access-report',
    50,
    null,
);
$report = (new PersonalDataAggregator($registry))->collect($request);

$assertSame(['reference_app', 'failing_app'], array_keys($report->providers()), 'Der feste Registry-Snapshot wurde verändert.');
$assertSame('complete', $report->providers()['reference_app']->status(), 'Der erfolgreiche Providerstatus fehlt.');
$assertSame('failed', $report->providers()['failing_app']->status(), 'Ein Providerfehler wurde nicht isoliert.');
$assertSame('Provider unavailable.', $report->providers()['failing_app']->restrictions()[0] ?? null, 'Interne Fehlerdetails sind in den Bericht gelangt.');
$assertSame(false, $report->isRegistrySnapshotComplete(), 'Ein Teilbericht wurde fälschlich für den Registry-Snapshot als vollständig markiert.');

echo "Provider aggregation test passed.\n";
