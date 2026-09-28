<?php

declare(strict_types=1);

$repository = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/Db/RetentionExecutionProfileRepository.php');
foreach (['historyForUid', "eq('changed_by'", 'setFirstResult(', 'setMaxResults('] as $contract) {
    if (!str_contains($repository, $contract)) {
        throw new RuntimeException('Subjectgebundener, paginierter Profilhistorienvertrag fehlt: ' . $contract);
    }
}

$provider = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/Privacy/DataProtectionPersonalDataProvider.php');
if (!str_contains($provider, 'historyForUid(')) {
    throw new RuntimeException('Der PersonalDataProvider liest die Profilhistorie nicht subjectgebunden und paginiert.');
}

echo "Retention execution profile repository contract passed.\n";
