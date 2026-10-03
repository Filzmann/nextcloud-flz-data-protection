<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\PublicApi\V2;

interface RetentionExecutionProvider {
    public function descriptor(): RetentionExecutionProviderDescriptor;
    /** @return list<RetentionExecutionPolicy> */
    public function policies(): array;
    public function plan(RetentionExecutionRequest $request): RetentionExecutionPage;
    public function execute(RetentionExecutionBatch $batch): RetentionExecutionResult;
}
