<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Controller;

use DomainException;
use InvalidArgumentException;
use OCA\FlzDataProtection\AppInfo\Application;
use OCA\FlzDataProtection\Service\RetentionExecutionActivationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;

final class RetentionExecutionActivationController extends Controller {
    public function __construct(IRequest $request, private RetentionExecutionActivationService $service, private IL10N $l10n) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoCSRFRequired]
    public function show(): JSONResponse {
        if (!$this->service->canConfigure()) return $this->denied();
        return new JSONResponse(['status'=>$this->service->status(), 'history'=>$this->service->history()]);
    }

    public function save(array $configuration): JSONResponse {
        if (!$this->service->canConfigure()) return $this->denied();
        try {
            return new JSONResponse(['status'=>$this->service->save($configuration)]);
        } catch (DomainException $error) {
            return new JSONResponse(['message'=>$this->l10n->t($error->getMessage())], Http::STATUS_CONFLICT);
        } catch (InvalidArgumentException $error) {
            return new JSONResponse(['message'=>$this->l10n->t($error->getMessage())], Http::STATUS_BAD_REQUEST);
        }
    }

    private function denied(): JSONResponse {
        return new JSONResponse(['message'=>$this->l10n->t('Zugriff verweigert.')], Http::STATUS_FORBIDDEN);
    }
}
