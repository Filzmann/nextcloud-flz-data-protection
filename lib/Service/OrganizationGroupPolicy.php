<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Service;

use OCA\FlzDataProtection\Contract\OrganizationSnapshotSource;
use OCP\IGroupManager;
use Throwable;

final class OrganizationGroupPolicy {
    private const DEFAULT_GROUPS = [
        'Datenschutzbeauftragte' => 'Datenschutzbeauftragte',
    ];

    public function __construct(
        private OrganizationSnapshotSource $source,
        private IGroupManager $groups,
    ) {}

    /** @return array{available:bool,message:string,options:list<array{groupId:string,label:string,kind:string,exists:bool}>} */
    public function state(): array {
        $options = $this->defaultOptions();
        try {
            $snapshot = $this->source->snapshot();
        } catch (Throwable) {
            return $this->unavailable('Das Organigramm konnte nicht gelesen werden. Es ist nur die vorhandene Datenschutz-Standardgruppe auswählbar.', $options);
        }
        if ($snapshot === null) {
            return $this->unavailable('LocalBase stellt derzeit kein Organigramm bereit. Es ist nur die vorhandene Datenschutz-Standardgruppe auswählbar.', $options);
        }
        if (($snapshot['contractVersion'] ?? null) !== '1.0' || ($snapshot['valid'] ?? null) !== true) {
            return $this->unavailable('Das Organigramm ist ungültig oder inkompatibel. Es ist nur die vorhandene Datenschutz-Standardgruppe auswählbar.', $options);
        }

        foreach (['roles' => 'Rolle', 'areas' => 'Bereich'] as $key => $kind) {
            if (!is_array($snapshot[$key] ?? null)) {
                return $this->unavailable('Das Organigramm ist unvollständig. Es ist nur die vorhandene Datenschutz-Standardgruppe auswählbar.', $this->defaultOptions());
            }
            foreach ($snapshot[$key] as $mapping) {
                $groupId = is_array($mapping) ? trim((string)($mapping['groupId'] ?? '')) : '';
                $label = is_array($mapping) ? trim((string)($mapping['label'] ?? '')) : '';
                if ($groupId === '' || $label === '') {
                    return $this->unavailable('Das Organigramm enthält eine ungültige Gruppenzuordnung. Es ist nur die vorhandene Datenschutz-Standardgruppe auswählbar.', $this->defaultOptions());
                }
                $options[$groupId] ??= [
                    'groupId' => $groupId,
                    'label' => $label,
                    'kind' => $kind,
                    'exists' => $this->groups->groupExists($groupId),
                ];
            }
        }
        $options = array_values($options);
        usort($options, static fn(array $left, array $right): int => strnatcasecmp($left['label'], $right['label']));
        return ['available' => true, 'message' => '', 'options' => $options];
    }

    public function isAllowed(string $groupId): bool {
        $groupId = trim($groupId);
        if ($groupId === '') return false;
        $state = $this->state();
        foreach ($state['options'] as $option) {
            if ($option['groupId'] === $groupId) return $option['exists'];
        }
        return false;
    }

    /** @return array{available:bool,message:string,options:list<array{groupId:string,label:string,kind:string,exists:bool}>} */
    private function unavailable(string $message, array $options): array {
        $options = array_values($options);
        usort($options, static fn(array $left, array $right): int => strnatcasecmp($left['label'], $right['label']));
        return ['available' => false, 'message' => $message, 'options' => $options];
    }

    /** @return array<string,array{groupId:string,label:string,kind:string,exists:bool}> */
    private function defaultOptions(): array {
        $options = [];
        foreach (self::DEFAULT_GROUPS as $groupId => $label) {
            $options[$groupId] = [
                'groupId' => $groupId,
                'label' => $label,
                'kind' => 'Datenschutz-Standardgruppe',
                'exists' => $this->groups->groupExists($groupId),
            ];
        }
        return $options;
    }
}
