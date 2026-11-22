<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || !isset($argv[1]))
{
    exit(2);
}
define('OC_CONSOLE', true);
require rtrim($argv[1], '/') . '/lib/base.php';
set_exception_handler(static function (Throwable $error): void
{
    fwrite(STDERR, 'Privacy organization probe failed: ' . $error::class . PHP_EOL);
    exit(1);
});
$source = OCP\Server::get(OCA\FlzDataProtection\Contract\OrganizationSnapshotSource::class);
if (!$source instanceof OCA\FlzDataProtection\Service\FlzOrganizationSnapshotSource)
{
    throw new RuntimeException('The canonical organization adapter is not registered.');
}
$snapshot = $source->snapshot();
if (($snapshot['contractVersion'] ?? null) !== '1.0' || ($snapshot['valid'] ?? null) !== true)
{
    throw new RuntimeException('The configured organization provider is not valid.');
}
echo json_encode([
    'provider' => 'flz_organization',
    'valid' => true,
    'roles' => count($snapshot['roles']),
    'areas' => count($snapshot['areas']),
], JSON_THROW_ON_ERROR), PHP_EOL;
