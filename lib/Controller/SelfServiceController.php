<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Controller;

use OCA\FlzDataProtection\AppInfo\Application;
use OCA\FlzDataProtection\Exception\AuthenticationRequiredException;
use OCA\FlzDataProtection\Service\SelfServiceReportService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;

final class SelfServiceController extends Controller {
    public function __construct(
        IRequest $request,
        private SelfServiceReportService $reports,
        private IL10N $l10n,
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
                ['message' => $this->l10n->t('Authentifizierung erforderlich.')],
                Http::STATUS_UNAUTHORIZED,
            );
        }
    }
}
