<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$info = (string)file_get_contents($root . '/appinfo/info.xml');
if (preg_match('/<version>([^<]+)<\/version>/', $info, $match) !== 1) {
    throw new RuntimeException('Die App-Version ist nicht lesbar.');
}
if (version_compare($match[1], '0.1.4', '<=')) {
    throw new RuntimeException('Die Retention-Migrationen werden auf einem bereits installierten Stand 0.1.4 nicht als Upgrade erkannt.');
}
foreach ([
    'Version000005Date202609290001.php',
    'Version000006Date202609290002.php',
    'Version000007Date202609290003.php',
] as $migration) {
    if (!is_file($root . '/lib/Migration/' . $migration)) {
        throw new RuntimeException('Erwartete Retention-Migration fehlt: ' . $migration);
    }
}

echo "Retention execution upgrade recognition test passed.\n";
