<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$interfacePath = $root . '/lib/Db/RiskScopeAuthorizationRepositoryInterface.php';
$repositoryPath = $root . '/lib/Db/RiskScopeAuthorizationRepository.php';
if (!is_file($interfacePath) || !is_file($repositoryPath)) {
    throw new RuntimeException('Die append-only Risikoscope-Ablage fehlt.');
}
$interface = (string)file_get_contents($interfacePath);
$repository = (string)file_get_contents($repositoryPath);

foreach (['latest(', 'history(', 'appendIfCurrent('] as $contract) {
    if (!str_contains($interface, $contract)) {
        throw new RuntimeException('Repositoryvertrag fehlt: ' . $contract);
    }
}
foreach (['appendIfCurrent(', "'revision' => \$expectedRevision + 1", 'executeStatement()', 'latest($scopeId)', 'DomainException'] as $contract) {
    if (!str_contains($repository, $contract)) {
        throw new RuntimeException('Atomarer Repositoryvertrag fehlt: ' . $contract);
    }
}
if (str_contains($repository, 'catch (Throwable')) {
    throw new RuntimeException('Beliebige technische Fehler dürfen nicht als fachlicher Konkurrenzkonflikt maskiert werden.');
}
foreach (['OCP\\DB\\Exception', 'REASON_UNIQUE_CONSTRAINT_VIOLATION', 'getReason()'] as $contract) {
    if (!str_contains($repository, $contract)) {
        throw new RuntimeException('Die native Unique-Constraint-Kollision wird nicht deterministisch als Konflikt abgebildet: ' . $contract);
    }
}
if (preg_match('/function\s+(update|delete)\s*\(/i', $repository) === 1) {
    throw new RuntimeException('Die Scope-Revisionsablage darf keinen Update- oder Löschpfad anbieten.');
}

$service = (string)file_get_contents($root . '/lib/Service/RiskScopeAuthorizationService.php');
if (!str_contains($service, 'RiskScopeAuthorizationRepositoryInterface')
    || !str_contains($service, 'appendIfCurrent(')
    || str_contains($service, 'setValueString(')) {
    throw new RuntimeException('Der Service umgeht die atomare Repositorygrenze.');
}

echo "Risk scope authorization repository contract passed.\n";
