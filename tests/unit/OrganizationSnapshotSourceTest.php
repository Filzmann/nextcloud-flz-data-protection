<?php

declare(strict_types=1);

use OCA\FlzDataProtection\Service\FlzOrganizationSnapshotSource;
use OCA\FlzDataProtection\Service\OrganizationGroupPolicy;
use OCA\FlzOrganization\PublicApi\V1\Contract;
use OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshotService;
use OCP\App\IAppManager;
use OCP\Server;

$sourceApps = new class implements IAppManager
{
    public array $enabled = ['flz_organization'];
    public bool $fail = false;

    public function isInstalled($appId): bool
    {
        if ($this->fail)
        {
            throw new RuntimeException('SYNTHETIC-SENSITIVE-PAYLOAD');
        }
        return in_array($appId, $this->enabled, true);
    }
};
$sourceLogger = new class implements \Psr\Log\LoggerInterface
{
    public array $warnings = [];

    public function emergency(string|Stringable $message, array $context = []): void {}
    public function alert(string|Stringable $message, array $context = []): void {}
    public function critical(string|Stringable $message, array $context = []): void {}
    public function error(string|Stringable $message, array $context = []): void {}
    public function notice(string|Stringable $message, array $context = []): void {}
    public function info(string|Stringable $message, array $context = []): void {}
    public function debug(string|Stringable $message, array $context = []): void {}
    public function log(mixed $level, string|Stringable $message, array $context = []): void {}

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->warnings[] = $context;
    }
};
$sourceValue = new class
{
    public string $version = '1.0';
    public bool $valid = true;

    public function contractVersion(): string { return $this->version; }
    public function isValid(): bool { return $this->valid; }
    public function roles(): array { return ['review' => ['groupId' => 'Org-Gruppe', 'label' => 'Prüfung']]; }
    public function areas(): array { return []; }
};
$sourceProvider = new OrganizationSnapshotService($sourceValue);
Server::$services = [OrganizationSnapshotService::class => $sourceProvider];
Server::$queries = 0;
$nativeSource = new FlzOrganizationSnapshotSource($sourceApps, $sourceLogger);
$sourceSnapshot = $nativeSource->snapshot();
if (($sourceSnapshot['roles']['review']['groupId'] ?? null) !== 'Org-Gruppe')
{
    throw new RuntimeException('Der aktivierte kanonische Organisationsprovider liefert auf Nextcloud 29–31 keine auswählbare Rolle.');
}

$modernSourceApps = new class implements IAppManager
{
    public bool $enabled = true;
    public bool $fail = false;

    public function isInstalled($appId): bool
    {
        throw new LogicException('Auf Nextcloud 32+ muss die aktuelle native Aktivierungsprüfung verwendet werden.');
    }

    public function isEnabledForAnyone(string $appId): bool
    {
        if ($this->fail)
        {
            throw new RuntimeException('SYNTHETIC-SENSITIVE-PAYLOAD');
        }
        return $this->enabled && $appId === 'flz_organization';
    }
};
$modernNativeSource = new FlzOrganizationSnapshotSource($modernSourceApps, $sourceLogger);
if (($modernNativeSource->snapshot()['roles']['review']['groupId'] ?? null) !== 'Org-Gruppe')
{
    throw new RuntimeException('Die aktuelle native Aktivierungsprüfung liefert keine Organisationsrolle.');
}
$modernSourceApps->enabled = false;
$queriesBeforeModernMissing = Server::$queries;
if ($modernNativeSource->snapshot() !== null || Server::$queries !== $queriesBeforeModernMissing)
{
    throw new RuntimeException('Ein aktuell deaktivierter Provider wird dennoch aufgelöst.');
}
$modernSourceApps->fail = true;
if ($modernNativeSource->snapshot() !== null || Server::$queries !== $queriesBeforeModernMissing)
{
    throw new RuntimeException('Eine fehlgeschlagene Aktivierungsprüfung verwendet dennoch Organisationsrechte.');
}
$modernSourceApps->fail = false;

$sourceApps->enabled = ['localbase'];
$queriesBeforeMissing = Server::$queries;
if ($nativeSource->snapshot() !== null || Server::$queries !== $queriesBeforeMissing)
{
    throw new RuntimeException('Ein fehlender Organisationsprovider wird über LocalBase oder einen geladenen Service ersetzt.');
}
$sourceApps->fail = true;
if ($nativeSource->snapshot() !== null || Server::$queries !== $queriesBeforeMissing)
{
    throw new RuntimeException('Eine fehlgeschlagene ältere Aktivierungsprüfung verwendet dennoch Organisationsrechte.');
}
$sourceApps->fail = false;
$sourceApps->enabled = ['flz_organization'];
Contract::$compatible = false;
if ($nativeSource->snapshot() !== null || Server::$queries !== $queriesBeforeMissing)
{
    throw new RuntimeException('Ein inkompatibler Provider wird vor dem V1-Handshake aufgelöst.');
}
Contract::$compatible = true;
$sourceValue->version = '2.0';
if ($nativeSource->snapshot() !== null)
{
    throw new RuntimeException('Eine inkompatible Snapshotversion wird für Rechtezuordnungen akzeptiert.');
}
$sourceValue->version = '1.0';
$sourceValue->valid = false;
if ($nativeSource->snapshot() !== null)
{
    throw new RuntimeException('Ein ungültiger Snapshot liefert Rollen für Datenschutzrechte.');
}
$sourceValue->valid = true;
$sourceProvider->fail = true;
if ($nativeSource->snapshot() !== null || $sourceLogger->warnings === [])
{
    throw new RuntimeException('Providerfehler bleiben nicht diagnostizierbar und fail-closed.');
}
$lastWarning = end($sourceLogger->warnings);
if (($lastWarning['exception_class'] ?? null) !== RuntimeException::class
    || str_contains(json_encode($sourceLogger->warnings, JSON_THROW_ON_ERROR), 'SYNTHETIC-SENSITIVE-PAYLOAD'))
{
    throw new RuntimeException('Providerlogging enthält sensible Fehlerpayloads oder keine Fehlerklasse.');
}
$sourceProvider->fail = false;
Server::$services = [];
if ($nativeSource->snapshot() !== null)
{
    throw new RuntimeException('Ein nicht auflösbarer Provider wird nicht verweigert.');
}
Server::$services = [OrganizationSnapshotService::class => new stdClass()];
if ($nativeSource->snapshot() !== null)
{
    throw new RuntimeException('Ein fremdes Serviceobjekt wird als Organisationsprovider verwendet.');
}
Server::$services = [OrganizationSnapshotService::class => $sourceProvider];
$sourceValueBeforeMalformed = $sourceProvider->value;
$sourceProvider->value = new stdClass();
if ($nativeSource->snapshot() !== null)
{
    throw new RuntimeException('Ein unvollständiger Snapshot wird als Rechtequelle verwendet.');
}
$sourceProvider->value = $sourceValueBeforeMalformed;
Server::$services = [];

echo "Canonical organization snapshot source tests passed.\n";
