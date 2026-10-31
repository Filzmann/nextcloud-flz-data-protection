<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Controller;

use InvalidArgumentException;
use OCA\FlzDataProtection\AppInfo\Application;
use OCA\FlzDataProtection\Service\DataProtectionPermissionChecker;
use OCA\FlzDataProtection\Service\PermissionAssignmentsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

final class RetentionSettingsController extends Controller {
    public function __construct(
        IRequest $request,
        private DataProtectionPermissionChecker $authorization,
        private PermissionAssignmentsService $settings,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    public function save(): JSONResponse {
        try {
            $params = $this->request->getParams();
            $changes = $params['assignments'] ?? null;
            if (!is_array($changes)) {
                throw new InvalidArgumentException('Die Rechtezuordnung ist ungültig.');
            }
            $canManageAll = $this->authorization->has(PermissionAssignmentsService::PERMISSION_ASSIGNMENTS_CONFIGURE);
            $canManageReview = $this->authorization->has(PermissionAssignmentsService::REVIEW_ASSIGNMENTS_CONFIGURE);
            if (!$canManageAll && (!$canManageReview || array_keys($changes) !== [PermissionAssignmentsService::RETENTION_REVIEW])) {
                return new JSONResponse(['message' => 'Zugriff verweigert.'], Http::STATUS_FORBIDDEN);
            }
            return new JSONResponse(['assignments' => $this->settings->save($changes)]);
        } catch (InvalidArgumentException $e) {
            return new JSONResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (\Throwable) {
            return new JSONResponse(['message' => 'Einstellungen konnten nicht gespeichert werden.'], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
