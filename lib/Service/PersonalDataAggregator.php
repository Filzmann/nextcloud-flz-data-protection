<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Service;

use OCA\FilzmannDataProtection\Model\AggregateReport;
use OCA\FilzmannDataProtection\Model\ProviderReport;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use Throwable;

final class PersonalDataAggregator {
    public function __construct(private PersonalDataProviderRegistry $registry) {
    }

    public function collect(PersonalDataRequest $request): AggregateReport {
        $reports = [];

        foreach ($this->registry->snapshot() as $appId => $provider) {
            $descriptor = $provider->descriptor();
            if (!$descriptor->supportsSubjectType($request->subject()->subjectType())) {
                $reports[$appId] = new ProviderReport('not_applicable', [], [], null);
                continue;
            }

            try {
                $providerRequest = $request->withPageLimit(min($request->pageLimit(), $descriptor->maxPageSize()));
                $page = $provider->collect($providerRequest);
                $reports[$appId] = new ProviderReport(
                    $page->status(),
                    $page->entries(),
                    $page->restrictions(),
                    $page->nextCursor(),
                );
            } catch (Throwable) {
                $reports[$appId] = new ProviderReport('failed', [], ['Provider unavailable.'], null);
            }
        }

        return new AggregateReport($reports);
    }
}

