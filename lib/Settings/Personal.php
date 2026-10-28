<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Settings;

use OCA\FlzDataProtection\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

final class Personal implements ISettings {
    public function getForm(): TemplateResponse {
        return new TemplateResponse(Application::APP_ID, 'personal');
    }

    public function getSection(): string {
        return Application::APP_ID;
    }

    public function getPriority(): int {
        return 10;
    }
}
