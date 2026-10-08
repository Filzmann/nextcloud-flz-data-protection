<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Migration;

use Closure;
use OCA\FlzDataProtection\BackgroundJob\RetentionExecutionJob;
use OCP\BackgroundJob\IJobList;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version000007Date202609290003 extends SimpleMigrationStep {public function __construct(private IJobList$jobs){}public function changeSchema(IOutput$output,Closure$schemaClosure,array$options):?ISchemaWrapper{$schema=$schemaClosure();if(!$schema->hasTable('flz_dp_retention_holds')){$table=$schema->createTable('flz_dp_retention_holds');$table->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);$table->addColumn('policy_id','string',['length'=>64,'notnull'=>true]);$table->addColumn('record_ref','string',['length'=>255,'notnull'=>true]);$table->addColumn('reason_code','string',['length'=>64,'notnull'=>true]);$table->addColumn('evidence_ref','string',['length'=>255,'notnull'=>true]);$table->addColumn('placed_by','string',['length'=>64,'notnull'=>true]);$table->addColumn('placed_at','datetime_immutable',['notnull'=>true]);$table->addColumn('review_due_at','datetime_immutable',['notnull'=>true]);$table->addColumn('released_by','string',['length'=>64,'notnull'=>false]);$table->addColumn('released_at','datetime_immutable',['notnull'=>false]);$table->setPrimaryKey(['id']);$table->addIndex(['policy_id','record_ref','released_at'],'flz_dp_ret_hold_active');$table->addIndex(['review_due_at','released_at'],'flz_dp_ret_hold_review');}return$schema;}public function postSchemaChange(IOutput$output,Closure$schemaClosure,array$options):void{if(!$this->jobs->has(RetentionExecutionJob::class,null))$this->jobs->add(RetentionExecutionJob::class);}}
