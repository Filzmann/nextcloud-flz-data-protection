<?php

declare(strict_types=1);

use OCA\FlzDataProtection\PublicApi\V1\DataSubjectRef;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataEntry;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FlzDataProtection\PublicApi\V1\ProviderDescriptor;
use OCA\FlzDataProtection\PublicApi\V1\Testing\PersonalDataProviderContractTestKit;

$assertSame = static function (mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Erwartet: ' . var_export($expected, true) . '; erhalten: ' . var_export($actual, true));
    }
};

$entryConstructorParameters = array_map(
    static fn (ReflectionParameter $parameter): string => $parameter->getName(),
    (new ReflectionMethod(OCA\FlzDataProtection\PublicApi\V1\PersonalDataEntry::class, '__construct'))->getParameters(),
);
$assertSame(true, in_array('reference', $entryConstructorParameters, true), 'Der öffentliche Datensatzvertrag besitzt keine stabile technische Referenz.');

$entry = new PersonalDataEntry(
    categoryId: 'booking',
    categoryLabel: 'Booking',
    reference: 'booking:17',
    summary: 'Synthetic booking',
    purpose: 'Room coordination',
    source: 'Authenticated user',
    recipientCategories: ['Instance users'],
    retention: 'Review required',
    thirdCountryTransfer: 'None declared by the provider',
    automatedDecision: 'None declared by the provider',
    thirdPartyContentNotice: null,
    attributes: [],
);
$assertSame('booking:17', $entry->reference(), 'Die stabile technische Referenz ist nicht lesbar.');

$emptyReferenceRejected = false;
try {
    new PersonalDataEntry(
        categoryId: 'booking',
        categoryLabel: 'Booking',
        reference: '',
        summary: 'Synthetic booking',
        purpose: 'Room coordination',
        source: 'Authenticated user',
        recipientCategories: ['Instance users'],
        retention: 'Review required',
        thirdCountryTransfer: 'None declared by the provider',
        automatedDecision: 'None declared by the provider',
        thirdPartyContentNotice: null,
        attributes: [],
    );
} catch (InvalidArgumentException) {
    $emptyReferenceRejected = true;
}
$assertSame(true, $emptyReferenceRejected, 'Eine leere technische Referenz wurde akzeptiert.');

$invalidVersionRejected = false;
try {
    new ProviderDescriptor('reference_app', 'Reference app', 'version-one', ['nextcloud-user'], ['personal-data'], 25);
} catch (InvalidArgumentException) {
    $invalidVersionRejected = true;
}
$assertSame(true, $invalidVersionRejected, 'Eine nicht versionsfähige Vertragskennung wurde akzeptiert.');

$duplicateSubjectRejected = false;
try {
    new ProviderDescriptor('reference_app', 'Reference app', '1.0', ['nextcloud-user', 'nextcloud-user'], ['personal-data'], 25);
} catch (InvalidArgumentException) {
    $duplicateSubjectRejected = true;
}
$assertSame(true, $duplicateSubjectRejected, 'Doppelte Subject-Typen wurden akzeptiert.');

$partialWithoutRestrictionRejected = false;
try {
    new PersonalDataPage('partial');
} catch (InvalidArgumentException) {
    $partialWithoutRestrictionRejected = true;
}
$assertSame(true, $partialWithoutRestrictionRejected, 'Ein unbegründeter Teilbericht wurde akzeptiert.');

$invalidCursorRejected = false;
try {
    new PersonalDataRequest(
        new DataSubjectRef('nextcloud-user', 'synthetic-user'),
        'de',
        'access-report',
        50,
        ['reference_app' => ''],
    );
} catch (InvalidArgumentException) {
    $invalidCursorRejected = true;
}
$assertSame(true, $invalidCursorRejected, 'Ein leerer Providercursor wurde akzeptiert.');

$seenCursor = null;
$seenLimit = null;
$provider = new class($seenCursor, $seenLimit) implements PersonalDataProvider {
    public function __construct(private ?string &$seenCursor, private ?int &$seenLimit) {
    }

    public function descriptor(): ProviderDescriptor {
        return new ProviderDescriptor('reference_app', 'Reference app', '1.0', ['nextcloud-user'], ['personal-data'], 25);
    }

    public function collect(PersonalDataRequest $request): PersonalDataPage {
        $this->seenCursor = $request->cursor();
        $this->seenLimit = $request->pageLimit();
        return new PersonalDataPage('not_applicable');
    }
};
$request = new PersonalDataRequest(
    new DataSubjectRef('nextcloud-user', 'synthetic-user'),
    'de',
    'access-report',
    50,
    ['reference_app' => 'opaque-reference-cursor', 'other_app' => 'opaque-other-cursor'],
);
PersonalDataProviderContractTestKit::verifyScenario($provider, $request);

$assertSame('opaque-reference-cursor', $seenCursor, 'Das Contract-Test-Kit hat einen fremden Providercursor übergeben.');
$assertSame(25, $seenLimit, 'Das Contract-Test-Kit hat die Paginggrenze des Providers überschritten.');

echo "Provider contract semantics test passed.\n";
