<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Service;

use InvalidArgumentException;
use OCA\FilzmannDataProtection\AppInfo\Application;
use OCP\IAppConfig;
use OCP\IGroupManager;

final class RetentionSettingsService {
    private const DEFAULT_REVIEWER_GROUPS = ['Datenschutzbeauftragte'];

    public function __construct(private IAppConfig $config, private IGroupManager $groups) {}

    public function reviewerGroups(): array {
        $groups = $this->config->getValueArray(Application::APP_ID, 'retention_reviewer_groups', self::DEFAULT_REVIEWER_GROUPS, true);
        return $this->normalizeGroups($groups);
    }

    public function toArray(): array {
        return [
            'reviewer_groups' => $this->reviewerGroups(),
        ];
    }

    public function save(array $payload): array {
        $reviewerGroups = $this->normalizeGroups($payload['reviewer_groups'] ?? self::DEFAULT_REVIEWER_GROUPS);
        foreach ($reviewerGroups as $groupId) {
            if (!$this->groups->groupExists($groupId)) throw new InvalidArgumentException('Prüfgruppe „' . $groupId . '“ existiert nicht.');
        }
        $this->config->setValueArray(Application::APP_ID, 'retention_reviewer_groups', $reviewerGroups, true);
        return $this->toArray();
    }

    private function normalizeGroups(array|string $value): array {
        if (is_string($value)) $value = preg_split('/[,\n\r]+/', $value) ?: [];
        $groups = array_values(array_unique(array_filter(array_map(static fn($group): string => trim((string)$group), $value))));
        sort($groups, SORT_NATURAL | SORT_FLAG_CASE);
        return $groups;
    }

}
