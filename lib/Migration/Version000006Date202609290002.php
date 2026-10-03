<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Separate technical opt-in history; absence keeps execution disabled. */
final class Version000006Date202609290002 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if ($schema->hasTable('fdp_retention_exec')) return null;
        $table = $schema->createTable('fdp_retention_exec');
        $table->addColumn('id', 'bigint', ['autoincrement'=>true,'notnull'=>true]);
        $table->addColumn('revision', 'integer', ['notnull'=>true]);
        $table->addColumn('enabled', 'boolean', ['notnull'=>true,'default'=>false]);
        $table->addColumn('approved_policy_ids', 'string', ['length'=>2048,'notnull'=>true]);
        $table->addColumn('backup_regular_days', 'integer', ['notnull'=>true]);
        $table->addColumn('backup_buffer_days', 'integer', ['notnull'=>true]);
        $table->addColumn('backup_verified_at', 'datetime_immutable', ['notnull'=>false]);
        $table->addColumn('restore_verified_at', 'datetime_immutable', ['notnull'=>false]);
        $table->addColumn('verification_due_at', 'datetime_immutable', ['notnull'=>false]);
        $table->addColumn('changed_by', 'string', ['length'=>64,'notnull'=>true]);
        $table->addColumn('created_at', 'datetime_immutable', ['notnull'=>true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['revision'], 'fdp_ret_exec_revision');
        return $schema;
    }
}
