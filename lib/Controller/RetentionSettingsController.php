<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Controller;

use InvalidArgumentException;
use OCA\FilzmannDataProtection\AppInfo\Application;
use OCA\FilzmannDataProtection\Service\RetentionAccessService;
use OCA\FilzmannDataProtection\Service\RetentionSettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

final class RetentionSettingsController extends Controller {
    public function __construct(
        IRequest $request,
        private RetentionAccessService $access,
        private RetentionSettingsService $settings,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    public function save(): JSONResponse {
        if (!$this->access->canConfigure()) {
            return new JSONResponse(['message' => 'Zugriff verweigert.'], Http::STATUS_FORBIDDEN);
        }
        try {
            $params = $this->request->getParams();
            return new JSONResponse($this->settings->save([
                'reviewer_groups' => $params['reviewer_groups'] ?? '',
            ]));
        } catch (InvalidArgumentException $e) {
            return new JSONResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (\Throwable) {
            return new JSONResponse(['message' => 'Einstellungen konnten nicht gespeichert werden.'], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
