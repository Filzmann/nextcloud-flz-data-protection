<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Controller;

use DateTimeImmutable;
use DateTimeZone;
use OCA\FilzmannDataProtection\AppInfo\Application;
use OCA\FilzmannDataProtection\Service\RetentionAccessService;
use OCA\FilzmannDataProtection\Service\RetentionPreviewAggregator;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

final class RetentionReviewController extends Controller {
    public function __construct(
        IRequest $request,
        private RetentionAccessService $access,
        private RetentionPreviewAggregator $previews,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function report(): JSONResponse {
        if (!$this->access->canReview()) {
            return new JSONResponse(['message' => 'Zugriff verweigert.'], Http::STATUS_FORBIDDEN);
        }
        $evaluatedAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM);
        return new JSONResponse($this->previews->collect($evaluatedAt, 200));
    }
}
