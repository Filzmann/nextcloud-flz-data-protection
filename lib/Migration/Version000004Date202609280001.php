<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version000004Date202609280001 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if ($schema->hasTable('fdp_retention_profile')) return null;

        $table = $schema->createTable('fdp_retention_profile');
        $table->addColumn('id', 'bigint', ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('revision', 'integer', ['notnull' => true]);
        $table->addColumn('profile_id', 'string', ['length' => 64, 'notnull' => true]);
        $table->addColumn('profile_revision', 'string', ['length' => 64, 'notnull' => true]);
        $table->addColumn('legal_evidence_ref', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('scope_reference', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('purpose_reference', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('necessity_ref', 'string', ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('impact_ref', 'string', ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('safeguards_ref', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('account_categories', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('all_accounts_employees', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('effective_at', 'datetime_immutable', ['notnull' => true]);
        $table->addColumn('legal_review_due_at', 'datetime_immutable', ['notnull' => true]);
        $table->addColumn('backup_regular_days', 'integer', ['notnull' => true]);
        $table->addColumn('backup_buffer_days', 'integer', ['notnull' => true]);
        $table->addColumn('backup_responsible', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('backup_scope', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('backup_evidence_ref', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('backup_evidence_at', 'datetime_immutable', ['notnull' => true]);
        $table->addColumn('backup_review_due_at', 'datetime_immutable', ['notnull' => true]);
        $table->addColumn('restore_test_ref', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('restore_tested_at', 'datetime_immutable', ['notnull' => true]);
        $table->addColumn('dpo_confirmed', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('changed_by', 'string', ['length' => 64, 'notnull' => true]);
        $table->addColumn('created_at', 'datetime_immutable', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['revision'], 'fdp_ret_prof_revision');

        return $schema;
    }
}
