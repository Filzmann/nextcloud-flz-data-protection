<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\PublicApi\V1\Testing;

use DomainException;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FilzmannDataProtection\Service\PersonalDataProviderRegistry;

final class PersonalDataProviderContractTestKit {
    public static function verifyScenario(
        PersonalDataProvider $provider,
        PersonalDataRequest $request,
    ): PersonalDataPage {
        $registry = new PersonalDataProviderRegistry();
        $registry->register($provider);
        $descriptor = $provider->descriptor();

        if (!$descriptor->supportsSubjectType($request->subject()->subjectType())) {
            throw new DomainException('The contract scenario subject type is not supported.');
        }

        $providerRequest = $request->forProvider(
            $descriptor->appId(),
            min($request->pageLimit(), $descriptor->maxPageSize()),
        );
        $page = $provider->collect($providerRequest);

        if ($page->nextCursor() !== null && $page->nextCursor() === $providerRequest->cursor()) {
            throw new DomainException('A provider cursor must progress between pages.');
        }

        return $page;
    }
}
