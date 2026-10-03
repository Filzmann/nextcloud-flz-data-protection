<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Controller;

use OCA\FilzmannDataProtection\AppInfo\Application;
use OCA\FilzmannDataProtection\Service\RetentionAccessService;
use OCA\FilzmannDataProtection\Service\TemporaryAdminAccessService;
use OCA\FilzmannDataProtection\Service\AdminHistoryRetentionPolicyService;
use OCA\FilzmannDataProtection\Service\RetentionExecutionProfileService;
use OCA\FilzmannDataProtection\Service\RetentionExecutionActivationService;
use OCA\FilzmannDataProtection\Service\RiskScopeAuthorizationService;
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
