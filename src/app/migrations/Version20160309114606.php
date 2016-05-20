<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds an account_limit field to the organization table
 */
class Version20160309114606 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $schema->getTable('organization')
            ->addColumn('account_limit', 'integer', ['length' => 255, 'notnull' => false]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->getTable('organization')->dropColumn('account_limit');
    }
}
