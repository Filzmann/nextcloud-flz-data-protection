<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Controller;

use OCA\FlzDataProtection\AppInfo\Application;
use OCA\FlzDataProtection\Service\RetentionAccessService;
use OCA\FlzDataProtection\Service\TemporaryAdminAccessService;
use OCA\FlzDataProtection\Service\AdminHistoryRetentionPolicyService;
use OCA\FlzDataProtection\Service\RetentionExecutionProfileService;
use OCA\FlzDataProtection\Service\RetentionExecutionActivationService;
use OCA\FlzDataProtection\Service\RiskScopeAuthorizationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

final class PageController extends Controller {
    public function __construct(
        IRequest $request,
        private RetentionAccessService $retentionAccess,
        private TemporaryAdminAccessService $adminAccess,
        private AdminHistoryRetentionPolicyService $adminHistoryRetention,
        private RetentionExecutionProfileService $retentionExecutionProfile,
        private RetentionExecutionActivationService $retentionExecutionActivation,
        private RiskScopeAuthorizationService $riskScopeAuthorization,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function index(): TemplateResponse {
        $canManageAdminAccess = $this->adminAccess->canManageGrants();
        $showMissingAdminGrant = $this->adminAccess->currentAdminNeedsGrant();
        $canConfigureRetentionExecutionProfile = $this->retentionExecutionProfile->canConfigure();
        return new TemplateResponse(Application::APP_ID, 'index', [
            'canReviewRetention' => $this->retentionAccess->canReview(),
            'canManageAdminAccess' => $canManageAdminAccess,
            'showMissingAdminGrant' => $showMissingAdminGrant,
            'showAdminAccessLink' => $showMissingAdminGrant && $canManageAdminAccess,
            'canConfigureAdminHistoryRetention' => $this->adminHistoryRetention->canConfigure(),
            'canConfigureRetentionExecutionProfile' => $canConfigureRetentionExecutionProfile,
            'canConfigureRetentionExecutionActivation' => $this->retentionExecutionActivation->canConfigure(),
            'canConfigureRiskScopeAuthorizations' => $this->riskScopeAuthorization->canConfigure(),
            'retentionExecutionProfileSetupRequired' => $canConfigureRetentionExecutionProfile
                && $this->retentionExecutionProfile->status()['setupRequired'] === true,
        ]);
    }
}
