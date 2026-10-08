<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Controller;

use DateTimeImmutable;
use DateTimeZone;
use OCA\FlzDataProtection\AppInfo\Application;
use OCA\FlzDataProtection\Service\RetentionAccessService;
use OCA\FlzDataProtection\Service\RetentionPreviewAggregator;
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
    public function report(?string $continuation = null): JSONResponse {
        if (!$this->access->canReview()) {
            return new JSONResponse(['message' => 'Zugriff verweigert.'], Http::STATUS_FORBIDDEN);
        }
        try {
            $evaluatedAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM);
            return new JSONResponse($this->previews->collect($evaluatedAt, 200, $continuation));
        } catch (\InvalidArgumentException) {
            return new JSONResponse(['message' => 'Die REVIEW-Fortsetzung ist ungültig oder nicht mehr verfügbar.'], Http::STATUS_BAD_REQUEST);
        }
    }
}
