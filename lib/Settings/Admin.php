<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Settings;

use OCA\FilzmannDataProtection\AppInfo\Application;
use OCA\FilzmannDataProtection\Service\RetentionSettingsService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

final class Admin implements ISettings {
    public function __construct(private RetentionSettingsService $settings) {}
    public function getForm(): TemplateResponse {
        return new TemplateResponse(Application::APP_ID, 'admin', ['settings' => $this->settings->toArray()]);
    }
    public function getSection(): string { return Application::APP_ID; }
    public function getPriority(): int { return 20; }
}
