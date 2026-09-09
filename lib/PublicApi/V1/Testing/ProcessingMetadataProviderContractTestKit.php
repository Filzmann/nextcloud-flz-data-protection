<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\PublicApi\V1\Testing;

use OCA\FilzmannDataProtection\PublicApi\V1\ProcessingMetadataCatalog;
use OCA\FilzmannDataProtection\PublicApi\V1\ProcessingMetadataProvider;
use OCA\FilzmannDataProtection\Service\ProcessingMetadataProviderRegistry;

final class ProcessingMetadataProviderContractTestKit {
    public static function verify(ProcessingMetadataProvider $provider): ProcessingMetadataCatalog {
        $registry = new ProcessingMetadataProviderRegistry();
        $registry->register($provider);
        return $provider->catalog();
    }
}
