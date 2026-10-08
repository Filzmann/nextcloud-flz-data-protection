<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Service;

use OCA\FlzDataProtection\Exception\AuthenticationRequiredException;
use OCA\FlzDataProtection\Model\ProviderReport;
use OCA\FlzDataProtection\PublicApi\V1\DataSubjectRef;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataEntry;
use OCA\FlzDataProtection\PublicApi\V1\PersonalDataRequest;
use OCP\IL10N;
use OCP\IUserSession;

final class SelfServiceReportService {
    public function __construct(
        private IUserSession $session,
        private IL10N $l10n,
        private PersonalDataAggregator $aggregator,
    ) {
    }

    /** @return array<string, mixed> */
    public function report(): array {
        $uid = $this->session->getUser()?->getUID();
        if ($uid === null || trim($uid) === '') {
            throw new AuthenticationRequiredException('Authentication required.');
        }

        $subject = new DataSubjectRef('nextcloud-user', $uid);
        $report = $this->aggregator->collect(new PersonalDataRequest(
            $subject,
            $this->l10n->getLanguageCode(),
            'access-report',
            100,
            [],
        ));

        $providers = [];
        foreach ($report->providers() as $appId => $providerReport) {
            $providers[$appId] = $this->providerReport($providerReport);
        }

        return [
            'subject' => [
                'type' => $subject->subjectType(),
                'id' => $subject->subjectId(),
            ],
            'coverageComplete' => $report->isCoverageComplete(),
            'registrySnapshotComplete' => $report->isRegistrySnapshotComplete(),
            'discoveryStatus' => $report->discoveryStatus(),
            'providers' => $providers,
        ];
    }

    /** @return array<string, mixed> */
    private function providerReport(ProviderReport $report): array {
        return [
            'displayName' => $report->displayName(),
            'status' => $report->status(),
            'entries' => array_map(
                fn (PersonalDataEntry $entry): array => $this->entry($entry),
                $report->entries(),
            ),
            'restrictions' => $report->restrictions(),
            'nextCursor' => $report->nextCursor(),
        ];
    }

    /** @return array<string, mixed> */
    private function entry(PersonalDataEntry $entry): array {
        return [
            'categoryId' => $entry->categoryId(),
            'categoryLabel' => $entry->categoryLabel(),
            'reference' => $entry->reference(),
            'summary' => $entry->summary(),
            'purpose' => $entry->purpose(),
            'source' => $entry->source(),
            'recipientCategories' => $entry->recipientCategories(),
            'retention' => $entry->retention(),
            'thirdCountryTransfer' => $entry->thirdCountryTransfer(),
            'automatedDecision' => $entry->automatedDecision(),
            'thirdPartyContentNotice' => $entry->thirdPartyContentNotice(),
            'attributes' => $entry->attributes(),
        ];
    }
}
