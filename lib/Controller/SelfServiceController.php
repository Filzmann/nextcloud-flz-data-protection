<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Controller;

use OCA\FilzmannDataProtection\AppInfo\Application;
use OCA\FilzmannDataProtection\Exception\AuthenticationRequiredException;
use OCA\FilzmannDataProtection\Service\SelfServiceReportService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

final class SelfServiceController extends Controller {
    public function __construct(
        IRequest $request,
        private SelfServiceReportService $reports,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function report(): JSONResponse {
        try {
            return new JSONResponse($this->reports->report());
        } catch (AuthenticationRequiredException) {
            return new JSONResponse(
                ['message' => 'Authentifizierung erforderlich.'],
                Http::STATUS_UNAUTHORIZED,
            );
        }
    }
}
