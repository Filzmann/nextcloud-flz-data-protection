<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Controller;

use OCA\FilzmannDataProtection\AppInfo\Application;
use OCA\FilzmannDataProtection\Service\RetentionAccessService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

final class PageController extends Controller {
    public function __construct(IRequest $request, private RetentionAccessService $retentionAccess) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function index(): TemplateResponse {
        return new TemplateResponse(Application::APP_ID, 'index', [
            'canReviewRetention' => $this->retentionAccess->canReview(),
        ]);
    }
}
