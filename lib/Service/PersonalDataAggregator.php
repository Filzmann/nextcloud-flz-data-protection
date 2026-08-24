<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Service;

use OCA\FilzmannDataProtection\Model\AggregateReport;
use OCA\FilzmannDataProtection\Model\ProviderCoverageProfile;
use OCA\FilzmannDataProtection\Model\ProviderReport;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCP\EventDispatcher\IEventDispatcher;
use Throwable;

final class PersonalDataAggregator {
    public function __construct(private IEventDispatcher $events) {
    }

    public function collect(PersonalDataRequest $request, ?ProviderCoverageProfile $coverage = null): AggregateReport {
        $registration = new RegisterPersonalDataProvidersEvent();
        $discoveryStatus = 'complete';
        try {
            $this->events->dispatchTyped($registration);
        } catch (Throwable) {
            $discoveryStatus = 'failed';
        }
        $reports = [];

        foreach ($registration->providers() as $appId => $provider) {
            $descriptor = $provider->descriptor();
            if (!$descriptor->supportsSubjectType($request->subject()->subjectType())) {
                $reports[$appId] = new ProviderReport('not_applicable', [], [], null, $descriptor->displayName());
                continue;
            }

            try {
                $providerRequest = $request->forProvider($appId, min($request->pageLimit(), $descriptor->maxPageSize()));
                $page = $provider->collect($providerRequest);
                $reports[$appId] = new ProviderReport(
                    $page->status(),
                    $page->entries(),
                    $page->restrictions(),
                    $page->nextCursor(),
                    $descriptor->displayName(),
                );
            } catch (Throwable) {
                $reports[$appId] = new ProviderReport('failed', [], ['Provider unavailable.'], null, $descriptor->displayName());
            }
        }

        foreach ($registration->registrationFailures() as $appId => $restriction) {
            if (!isset($reports[$appId])) {
                $reports[$appId] = new ProviderReport('missing', [], [$restriction], null);
            }
        }

        foreach ($coverage?->expectedAppIds() ?? [] as $appId) {
            if (!isset($reports[$appId])) {
                $reports[$appId] = new ProviderReport('missing', [], ['Expected provider unavailable.'], null);
            }
        }

        ksort($reports, SORT_STRING);

        return new AggregateReport($reports, $coverage !== null, $discoveryStatus);
    }
}
