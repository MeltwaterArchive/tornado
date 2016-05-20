<?php

namespace Command;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Output master SQL file for mysql
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Application
 * @author      Ollie Parsley <ollie@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class MigrationsMasterSql extends MigrateCommand
{
    const VERSION_FIRST = 0;
    const VERSION_LATEST = 'latest';

    private $container;

    use MigrationsTrait;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container The container from the application
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct(null);
        $this->container = $container;
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('migrations:mastersql')
            ->setDescription('Update the master.sql with migration changes')
            ->addOption('from', null, InputOption::VALUE_OPTIONAL, 'Start from a specific version', self::VERSION_FIRST)
            ->addOption('to', null, InputOption::VALUE_OPTIONAL, 'Version we want to migrate to', self::VERSION_LATEST)
            ->addOption('write-sql', null, InputOption::VALUE_REQUIRED, 'The path to output the migration SQL file')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Only mysql is currently supported')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute the migration as a dry run.')
            ->addOption('query-time', null, InputOption::VALUE_NONE, 'Time all the queries individually.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command executes a migration to a specified version or the latest available version:

    <info>%command.full_name% --write-sql=/tmp/some_file.sql --type=mysql</info>

You can optionally manually specify the version you wish to migrate to:

    <info>%command.full_name% --from=VERSION --to=VERSION --write-sql=/tmp/some_file.sql --type=mysql</info>

EOT
            );

        AbstractCommand::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setMigrationConfiguration($this->getMigrationsConfig($this->container, true));
        
        // Check we have the write-sql argument
        if (!$input->getOption('write-sql')) {
            throw new \InvalidArgumentException('--write-sql=path is required');
        }

        // Check we have the type
        if (!$input->getOption('type')) {
            throw new \InvalidArgumentException('--type=mysql is required');
        }

        $fromVersion = $input->getOption('from');
        $toVersion = $input->getOption('to');
        $dbDriver = $input->getOption('type');
        $outputPath = $input->getOption('write-sql');

        $configuration = $this->getMigrationConfiguration($input, $output);
        $direction = $this->getMigrationDirection($fromVersion, $toVersion);

        $initialSchema = $this->getSchemaAtSpecificVersion($configuration, Version::DIRECTION_UP, $fromVersion);

        $targetSchema = $this->getSchemaAtSpecificVersion($configuration, $direction, $toVersion, $fromVersion);

        $addDropTables = $toVersion === self::VERSION_LATEST && $fromVersion === self::VERSION_FIRST;
        $sqlStatements = $this->getSchemaDiff($dbDriver, $initialSchema, $targetSchema, $addDropTables);
        $this->outputSqlFile($outputPath, $sqlStatements);
    }

    /**
     * Output the sqlite file to file
     *
     * @param string $path
     * @param string $content
     */
    protected function outputSqlFile($path, $content)
    {
        file_put_contents($path, $content);
    }

    /**
     * Builds an Schema object from migration to an specific version
     *
     * @param Configuration $configuration
     * @param string $direction If is up or down migration
     * @param string $startVersion The version of the database we want to build
     * @param string $fromVersion The version of the database we want to start rolling back
     *
     * @return Schema
     */
    private function getSchemaAtSpecificVersion(
        Configuration $configuration,
        $direction,
        $startVersion,
        $fromVersion = null
    ) {
        $migrationsToRun = $configuration->getMigrations();

        if ($direction == Version::DIRECTION_DOWN) {
            krsort($migrationsToRun);
            $schema = $this->getSchemaAtSpecificVersion($configuration, Version::DIRECTION_UP, $fromVersion);
        } else {
            ksort($migrationsToRun);
            $schema = new Schema();
        }

        if ($startVersion === self::VERSION_FIRST) {
            return $schema;
        }

        // Process the migrations
        foreach ($migrationsToRun as $version => $migration) {
            if ($direction === Version::DIRECTION_UP &&
                $startVersion !== self::VERSION_LATEST &&
                $version > $startVersion
            ) {
                break;
            }

            if ($direction === Version::DIRECTION_DOWN &&
                $version <= $startVersion
            ) {
                break;
            }

            $migration = $migration->getMigration();
            $migration->{'pre' . $direction}($schema);
            $migration->{$direction}($schema);
            $migration->{'post' . $direction}($schema);
        }
        return $schema;
    }

    /**
     * Figure out if the migration going up or down
     *
     * @param int $fromVersion
     * @param int|string $toVersion
     * @return string
     */
    private function getMigrationDirection($fromVersion, $toVersion)
    {
        $toVersion = $toVersion === self::VERSION_LATEST ? $fromVersion + 1 : $toVersion;
        return $toVersion > $fromVersion ? Version::DIRECTION_UP : Version::DIRECTION_DOWN;
    }

    /**
     * Returns a diff of migration to be run between two database versions
     *
     * @param string $type The driver type
     * @param Schema $initialSchema The starting point
     * @param Schema $targetSchema The final schema we want to go to
     * @param bool $dropTables If we want to drop tables. Useful for full database dump
     *
     * @return array
     */
    private function getSchemaDiff($type, $initialSchema, $targetSchema, $dropTables = false)
    {
        // Get all the new sql statements to get us to the new schema
        $sqlStatements = array();

        // Database specific schema manager specific
        $schemaDiffProvider = $this->container->get("doctrine.dbal.lazy_schema_diff_provider.$type");

        foreach ($schemaDiffProvider->getSqlDiffToMigrate($initialSchema, $targetSchema) as $sqlStatement) {
            $sqlStatements[] = $sqlStatement . ';';
        }

        if ($dropTables) {
            foreach ($targetSchema->getTableNames() as $tableName) {
                $fixedTableName = str_replace("public.", "", $tableName);
                array_unshift($sqlStatements, "DROP TABLE IF EXISTS $fixedTableName;");
            }
        }

        // Concatenate to get a string ready for sending elsewhere
        return join("\r\n", $sqlStatements) . "\r\n";
    }
}
