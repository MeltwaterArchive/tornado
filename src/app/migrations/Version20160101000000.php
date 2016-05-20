<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * The initial database set up
 */
class Version20160101000000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Organization
        $organization = $schema->createTable('organization');
        $organization->addColumn('id', 'integer', ['autoincrement' => true]);
        $organization->addColumn('name', 'string', ['length' => 255]);
        $organization->addColumn('skin', 'string', ['length' => 255, 'notnull' => false]);
        $organization->addColumn('jwt_secret', 'string', ['length' => 255, 'notnull' => false]);
        $organization->addColumn('permissions', 'string', ['length' => 255, 'notnull' => false]);
        $organization->setPrimaryKey(['id']);
        $organization->addOption('engine', 'InnoDB');
        
        // Agency
        $agency = $schema->createTable('agency');
        $agency->addColumn('id', 'integer', ['autoincrement' => true]);
        $agency->addColumn('organization_id', 'integer');
        $agency->addColumn('name', 'string', ['length' => 255]);
        $agency->addColumn('datasift_username', 'string', ['length' => 255]);
        $agency->addColumn('datasift_apikey', 'string', ['length' => 45, 'notnull' => false]);
        $agency->addColumn('skin', 'string', ['length' => 255, 'notnull' => false]);
        $agency->setPrimaryKey(['id']);
        $agency->addOption('engine', 'InnoDB');
        $agency->addNamedForeignKeyConstraint(
            'fk_agency_organization_1',
            'organization',
            ['organization_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );
        $agency->addUniqueIndex(['datasift_username'], 'unique_agency_ds_username', ['order' => 'asc']);
        $agency->addUniqueIndex(['datasift_apikey'], 'unique_agency_ds_apikey', ['order' => 'asc']);

        // Brand
        $brand = $schema->createTable('brand');
        $brand->addColumn('id', 'integer', ['autoincrement' => true]);
        $brand->addColumn('agency_id', 'integer');
        $brand->addColumn('name', 'string', ['length' => 255]);
        $brand->addColumn('datasift_identity_id', 'string', ['length' => 45, 'notnull' => false]);
        $brand->addColumn('datasift_apikey', 'string', ['length' => 45, 'notnull' => false]);
        $brand->addColumn('target_permissions', 'string', ['length' => 255, 'notnull' => false]);
        $brand->setPrimaryKey(['id']);
        $brand->addOption('engine', 'InnoDB');
        $brand->addNamedForeignKeyConstraint(
            'fk_brand_agency_1',
            'agency',
            ['agency_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );
        $brand->addIndex(['agency_id'], 'fk_brand_agency_1_idx', [], ['order' => 'asc']);
        $brand->addUniqueIndex(['datasift_identity_id'], 'unique_brand_ds_indetity', ['order' => 'asc']);
        $brand->addUniqueIndex(['datasift_apikey'], 'unique_brand_ds_apikey', ['order' => 'asc']);
        
        // Project
        $project = $schema->createTable('project');
        $project->addColumn('id', 'integer', ['autoincrement' => true]);
        $project->addColumn('brand_id', 'integer', ['notnull' => false]);
        $project->addColumn('name', 'string', ['length' => 255]);
        $project->addColumn('type', 'smallint', ['default' => '0']);
        $project->addColumn('fresh', 'smallint', ['default' => '1']);
        $project->addColumn('recording_filter', 'smallint', ['default' => '0']);
        $project->addColumn('created_at', 'integer');
        $project->setPrimaryKey(['id']);
        $project->addOption('engine', 'InnoDB');
        $project->addNamedForeignKeyConstraint(
            'fk_project_brand1',
            'brand',
            ['brand_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );
        $project->addUniqueIndex(['brand_id', 'name'], 'unique_project_brand_idx', ['order' => 'asc']);
        
        // Recording
        $recording = $schema->createTable('recording');
        $recording->addColumn('id', 'integer', ['autoincrement' => true]);
        $recording->addColumn('brand_id', 'integer');
        $recording->addColumn('project_id', 'integer', ['notnull' => false]);
        $recording->addColumn('datasift_recording_id', 'string', ['length' => 255]);
        $recording->addColumn('hash', 'string', ['length' => 255]);
        $recording->addColumn('name', 'string', ['length' => 255]);
        $recording->addColumn('status', 'string', ['length' => 10, 'default' => 'started']);
        $recording->addColumn('csdl', 'blob', ['notnull' => false]); //should be longtext
        $recording->addColumn('vqb_generated', 'smallint');
        $recording->addColumn('created_at', 'integer');
        $recording->setPrimaryKey(['id']);
        $recording->addOption('engine', 'InnoDB');
        $recording->addIndex(['brand_id'], 'fk_recording_brand1_idx', [], ['order' => 'asc']);
        $recording->addIndex(['project_id'], 'fk_recording_project1_idx', [], ['order' => 'asc']);
        $recording->addNamedForeignKeyConstraint(
            'fk_recording_brand1',
            'brand',
            ['brand_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );
        $recording->addNamedForeignKeyConstraint(
            'fk_recording_project1',
            'project',
            ['project_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => 'NO ACTION']
        );
        
        // Dataset
        $dataset = $schema->createTable('dataset');
        $dataset->addColumn('id', 'integer', ['autoincrement' => true]);
        $dataset->addColumn('brand_id', 'integer');
        $dataset->addColumn('recording_id', 'integer');
        $dataset->addColumn('name', 'string', ['length' => 255]);
        $dataset->addColumn('dimensions', 'string', ['length' => 255]);
        $dataset->addColumn('visibility', 'string', ['length' => 10]);
        $dataset->addColumn('data', 'blob'); // should be longtext
        $dataset->addColumn('analysis_type', 'string', ['length' => 45, 'notnull' => false]);
        $dataset->addColumn('filter', 'blob', ['notnull' => false]); // should be longtext
        $dataset->addColumn('schedule', 'integer', ['notnull' => false]);
        $dataset->addColumn('schedule_units', 'string', ['length' => 45, 'notnull' => false]);
        $dataset->addColumn('time_range', 'string', ['length' => 45, 'notnull' => false]);
        $dataset->addColumn('status', 'string', ['length' => 10, 'notnull' => false]);
        $dataset->addColumn('last_refreshed', 'integer', ['notnull' => false]);
        $dataset->addColumn('created_at', 'integer', ['notnull' => false]);
        $dataset->addColumn('updated_at', 'integer', ['notnull' => false]);
        $dataset->setPrimaryKey(['id']);
        $dataset->addOption('engine', 'InnoDB');
        $dataset->addNamedForeignKeyConstraint(
            'fk_dataset_brand_1',
            'brand',
            ['brand_id'],
            ['id'],
            ['onDelete' => 'NO ACTION', 'onUpdate' => 'NO ACTION']
        );
        $dataset->addNamedForeignKeyConstraint(
            'fk_dataset_recording_1',
            'recording',
            ['recording_id'],
            ['id'],
            ['onDelete' => 'NO ACTION', 'onUpdate' => 'NO ACTION']
        );
        $dataset->addIndex(['brand_id'], 'fk_dataset_brand_1', [], ['order' => 'asc']);
        $dataset->addIndex(['recording_id'], 'fk_dataset_recording_1', [], ['order' => 'asc']);

        // Workbook
        $workbook = $schema->createTable('workbook');
        $workbook->addColumn('id', 'integer', ['autoincrement' => true]);
        $workbook->addColumn('project_id', 'integer', ['notnull' => false]);
        $workbook->addColumn('name', 'string', ['length' => 255, 'notnull' => false]);
        $workbook->addColumn('recording_id', 'integer', ['notnull' => false]);
        $workbook->addColumn('rank', 'integer', ['notnull' => false]);
        $workbook->setPrimaryKey(['id']);
        $workbook->addOption('engine', 'InnoDB');
        $workbook->addNamedForeignKeyConstraint(
            'fk_workbook_project',
            'project',
            ['project_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );
        $workbook->addNamedForeignKeyConstraint(
            'fk_workbook_recording',
            'recording',
            ['recording_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );
        $workbook->addIndex(['project_id'], 'fk_workbook_project_idx', [], ['order' => 'asc']);
        $workbook->addIndex(['recording_id'], 'fk_workbook_recording_idx', [], ['order' => 'asc']);

        // Worksheet
        $worksheet = $schema->createTable('worksheet');
        $worksheet->addColumn('id', 'integer', ['autoincrement' => true]);
        $worksheet->addColumn('workbook_id', 'integer');
        $worksheet->addColumn('name', 'string', ['length' => 255]);
        $worksheet->addColumn('rank', 'integer');
        $worksheet->addColumn('comparison', 'string', ['length' => 10, 'default' => 'baseline']);
        $worksheet->addColumn('measurement', 'string', ['length' => 15, 'default' => 'unique_authors']);
        $worksheet->addColumn('chart_type', 'string', ['length' => 10, 'default' => 'tornado']);
        $worksheet->addColumn('analysis_type', 'string', ['length' => 10, 'default' => 'freqDist']);
        $worksheet->addColumn('secondary_recording_id', 'integer', ['notnull' => false]);
        $worksheet->addColumn('secondary_recording_filters', 'blob', ['notnull' => false]);
        $worksheet->addColumn('baseline_dataset_id', 'integer', ['notnull' => false]);
        $worksheet->addColumn('parent_worksheet_id', 'integer', ['notnull' => false]);
        $worksheet->addColumn('filters', 'blob', ['notnull' => false]);
        $worksheet->addColumn('dimensions', 'blob');
        $worksheet->addColumn('start', 'integer', ['notnull' => false]);
        $worksheet->addColumn('end', 'integer', ['notnull' => false]);
        $worksheet->addColumn('created_at', 'integer', ['notnull' => false]);
        $worksheet->addColumn('updated_at', 'integer', ['notnull' => false]);
        $worksheet->setPrimaryKey(['id']);
        $worksheet->addOption('engine', 'InnoDB');
        $worksheet->addNamedForeignKeyConstraint(
            'fk_worksheet_recording2',
            'recording',
            ['secondary_recording_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => 'NO ACTION']
        );
        $worksheet->addNamedForeignKeyConstraint(
            'fk_worksheet_dataset1',
            'dataset',
            ['baseline_dataset_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => 'NO ACTION']
        );
        $worksheet->addNamedForeignKeyConstraint(
            'fk_parent_worksheet',
            'worksheet',
            ['parent_worksheet_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );
        $worksheet->addNamedForeignKeyConstraint(
            'fk_worksheet_workbook',
            'workbook',
            ['workbook_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );
        $worksheet->addUniqueIndex(['workbook_id', 'name'], 'unique_name_workbook_id', ['order' => 'asc']);
        $worksheet->addIndex(['secondary_recording_id'], 'fk_worksheet_recording2_idx', [], ['order' => 'asc']);
        $worksheet->addIndex(['baseline_dataset_id'], 'fk_worksheet_dataset1_idx', [], ['order' => 'asc']);
        $worksheet->addIndex(['workbook_id'], 'fk_worksheet_workbook_idx', [], ['order' => 'asc']);
        $worksheet->addIndex(['parent_worksheet_id'], 'fk_worksheet_worksheet1_idx', [], ['order' => 'asc']);

        // Chart
        $chart = $schema->createTable('chart');
        $chart->addColumn('id', 'integer', ['autoincrement' => true]);
        $chart->addColumn('worksheet_id', 'integer');
        $chart->addColumn('name', 'string', ['length' => 255]);
        $chart->addColumn('rank', 'integer');
        $chart->addColumn('type', 'string', ['length' => 20]);
        $chart->addColumn('data', 'blob');
        $chart->setPrimaryKey(['id']);
        $chart->addOption('engine', 'InnoDB');
        $chart->addNamedForeignKeyConstraint(
            'fk_chart_worksheet1',
            'worksheet',
            ['worksheet_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE']
        );
        $chart->addIndex(['worksheet_id'], 'fk_chart_worksheet1_idx', [], ['order' => 'asc']);
        
        // User
        $user = $schema->createTable('user');
        $user->addColumn('id', 'integer', ['autoincrement' => true]);
        $user->addColumn('organization_id', 'integer');
        $user->addColumn('email', 'string', ['length' => 255]);
        $user->addColumn('password', 'string', ['length' => 255]);
        $user->addColumn('username', 'string', ['length' => 255]);
        $user->addColumn('type', 'integer', ['default' => '0']);
        $user->setPrimaryKey(['id']);
        $user->addOption('engine', 'InnoDB');
        $user->addNamedForeignKeyConstraint(
            'fk_user_organization1',
            'organization',
            ['organization_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );
        $user->addIndex(['organization_id'], 'fk_user_organization1_idx', [], ['order' => 'asc']);
        $user->addUniqueIndex(['organization_id', 'email'], 'unique_key_email_org', ['order' => 'asc']);

        // Users brands
        $usersBrands = $schema->createTable('users_brands');
        $usersBrands->addColumn('user_id', 'integer');
        $usersBrands->addColumn('brand_id', 'integer');
        $usersBrands->addOption('engine', 'InnoDB');
        $usersBrands->addNamedForeignKeyConstraint(
            'fk_table1_user1',
            'user',
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );
        $usersBrands->addNamedForeignKeyConstraint(
            'fk_users_projects_brand1',
            'brand',
            ['brand_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );
        $usersBrands->addIndex(['user_id'], 'fk_table1_user1_idx', [], ['order' => 'asc']);
        $usersBrands->addIndex(['brand_id'], 'fk_users_projects_brand1_idx', [], ['order' => 'asc']);
        $usersBrands->addUniqueIndex(['user_id', 'brand_id'], 'user_brand_unique_idx', ['order' => 'asc']);

        // Users agencies
        $usersAgencies = $schema->createTable('users_agencies');
        $usersAgencies->addColumn('user_id', 'integer');
        $usersAgencies->addColumn('agency_id', 'integer');
        $usersAgencies->addOption('engine', 'InnoDB');
        $usersAgencies->addNamedForeignKeyConstraint(
            'fk_users_agencies_user1',
            'user',
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );
        $usersAgencies->addNamedForeignKeyConstraint(
            'fk_users_agencies_agency1',
            'agency',
            ['agency_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );
        $usersAgencies->addIndex(['user_id'], 'fk_users_agencies_user1_idx', [], ['order' => 'asc']);
        $usersAgencies->addIndex(['agency_id'], 'fk_users_agencies_agency1_idx', [], ['order' => 'asc']);
        $usersAgencies->addUniqueIndex(['agency_id', 'user_id'], 'user_agency_unique_idx', ['order' => 'asc']);

        // Role
        $role = $schema->createTable('role');
        $role->addColumn('id', 'integer', ['autoincrement' => true]);
        $role->addColumn('name', 'string', ['length' => 64]);
        $role->addOption('engine', 'InnoDB');
        $role->setPrimaryKey(['id']);

        // Users roles
        $usersRoles = $schema->createTable('users_roles');
        $usersRoles->addColumn('role_id', 'integer');
        $usersRoles->addColumn('user_id', 'integer');
        $usersRoles->addOption('engine', 'InnoDB');
        $usersRoles->addNamedForeignKeyConstraint(
            'fk_users_roles_role1',
            'role',
            ['role_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );
        $usersRoles->addNamedForeignKeyConstraint(
            'fk_users_roles_user1',
            'user',
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );
        $usersRoles->addIndex(['role_id'], 'fk_users_roles_role1_idx', [], ['order' => 'asc']);
        $usersRoles->addIndex(['user_id'], 'fk_users_roles_user1_idx', [], ['order' => 'asc']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        //Drop all the tables
        $schema->dropTable('organization');
        $schema->dropTable('agency');
        $schema->dropTable('brand');
        $schema->dropTable('project');
        $schema->dropTable('recording');
        $schema->dropTable('dataset');
        $schema->dropTable('workbook');
        $schema->dropTable('worksheet');
        $schema->dropTable('chart');
        $schema->dropTable('user');
        $schema->dropTable('users_brands');
        $schema->dropTable('users_agencies');
        $schema->dropTable('role');
        $schema->dropTable('users_roles');
    }
}
