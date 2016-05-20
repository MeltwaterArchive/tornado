<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160316160804 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $options = ['length' => 255, 'default' => "NULL"];
        $schema->getTable('brand')->addColumn('datasift_username', 'string', $options);
        $schema->getTable('brand')->dropIndex('unique_brand_ds_indetity');
        $schema->getTable('brand')->dropIndex('unique_brand_ds_apikey');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->getTable('brand')->dropColumn('datasift_username');
        $schema->getTable('brand')->addUniqueIndex(['datasift_identity_id'], 'unique_brand_ds_indetity', ['ASC']);
        $schema->getTable('brand')->addUniqueIndex(['datasift_apikey'], 'unique_brand_ds_apikey', ['ASC']);
    }
}
