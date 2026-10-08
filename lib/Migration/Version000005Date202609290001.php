<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version000005Date202609290001 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if ($schema->hasTable('flz_dp_risk_scope_auth')) {
            return null;
        }

        $table = $schema->createTable('flz_dp_risk_scope_auth');
        $table->addColumn('id', 'bigint', ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('scope_id', 'string', ['length' => 160, 'notnull' => true]);
        $table->addColumn('revision', 'integer', ['notnull' => true]);
        $table->addColumn('schema_version', 'string', ['length' => 16, 'notnull' => true]);
        $table->addColumn('enabled', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('policy_revision', 'string', ['length' => 64, 'notnull' => true]);
        $table->addColumn('authorization_ref', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('effective_at', 'datetime_immutable', ['notnull' => true]);
        $table->addColumn('expires_at', 'datetime_immutable', ['notnull' => true]);
        $table->addColumn('dpo_confirmed', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('created_at', 'datetime_immutable', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['scope_id', 'revision'], 'flz_dp_risk_scope_rev');

        return $schema;
    }
}
