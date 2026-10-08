<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\PublicApi\V1;

interface RetentionProvider {
    public function descriptor(): RetentionProviderDescriptor;

    /** @return list<RetentionPolicy> */
    public function policies(): array;

    public function preview(RetentionPreviewRequest $request): RetentionPreviewPage;
}
