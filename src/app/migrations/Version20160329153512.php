<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160329153512 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sample = $schema->createTable('recording_sample');
        $sample->addColumn('id', 'integer', ['autoincrement' => true]);
        $sample->addColumn('recording_id', 'integer', ['notnull' => true]);
        $sample->addColumn('data', 'blob');
        $sample->addColumn('created_at', 'integer');
        $sample->setPrimaryKey(['id']);
        $sample->addOption('engine', 'InnoDB');
        $sample->addNamedForeignKeyConstraint(
            'fk_recording_sample_recording_1',
            'recording',
            ['recording_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => 'NO ACTION']
        );

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('recording_sample');
    }
}
