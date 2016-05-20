<?php

namespace Test\Command;

use Mockery;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Application as ConsoleApplication;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOMySql;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Migrations\Provider\LazySchemaDiffProvider;
use Test\DataSift\ReflectionAccess;
use Command\MigrationsMasterSql;

/**
 * MigrationsMasterSqlTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Command
 * @author      Ollie Parsley <ollie@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass  \Command\MigrationsMasterSql
 */
class MigrationsMasterSqlTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;
    use \Test\DataSift\ApplicationBuilder;
    
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer()
    {
        
        $original = Mockery::mock('Doctrine\DBAL\Migrations\Provider\SchemaDiffProviderInterface');
        $original->shouldReceive('getSqlDiffToMigrate')
                ->andReturn(['SOME SQL', 'SOME MORE SQL']);
        $lazySchemaDiffProvider = LazySchemaDiffProvider::fromDefaultProxyFacyoryConfiguration($original);
        
        $container = Mockery::mock('\Symfony\Component\DependencyInjection\ContainerInterface');
        $container->shouldReceive('getParameter')
                ->with('db.migrations.path')
                ->andReturn('src/app/migrations');
        $container->shouldReceive('get')
                ->with('doctrine.dbal.lazy_schema_diff_provider.mysql')
                ->andReturn($lazySchemaDiffProvider);
        $container->shouldReceive('get')
                ->with('doctrine.dbal.lazy_schema_diff_provider.sqlite')
                ->andReturn($lazySchemaDiffProvider);
        $container->shouldReceive('get')
                ->with('doctrine.dbal.lazy_schema_diff_provider.foo')
                ->andThrow(new \Exception('non-existent service'));
        $container->shouldReceive('get')
                ->with('doctrine.dbal.connection.dummy.sqlite')
                ->andReturn(new ForcedMySQLPlatformConnection(array(), new PDOMySql\Driver()));
        return $container;
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *
     * @return \Symfony\Component\Console\Tester\CommandTester
     */
    protected function provideCommandTester(ContainerInterface $container)
    {
        $application = new ConsoleApplication();
        $helpers = array('dialog' => new DialogHelper());
        $helperSet = new HelperSet($helpers);
        $application->setHelperSet($helperSet);
        $command = new MigrationsMasterSql($container);
        $command->setApplication($application);
        $connection = new ForcedMySQLPlatformConnection(array(), new PDOMySql\Driver());
        $config = new Configuration($connection);
        $config->setMigrationsNamespace('DoctrineMigrations');
        $config->setMigrationsDirectory($container->getParameter('db.migrations.path'));
        $config->registerMigrationsFromDirectory($container->getParameter('db.migrations.path'));
        $command->setMigrationConfiguration($config);
        return new CommandTester($command);
    }

    /**
     * @covers ::__construct
     * @covers ::configure
     */
    public function testConfigure()
    {
        $container = $this->getContainer();
        $command = new MigrationsMasterSql($container);
        $this->assertEquals('migrations:mastersql', $command->getName());
        $this->assertNotEmpty($command->getDescription());
        $this->assertNotEmpty($command->getHelp());
        
        $definition = $command->getDefinition();
        $this->assertInstanceOf('Symfony\Component\Console\Input\InputDefinition', $definition);
        $this->assertTrue($definition->hasOption('from'), "Missing argument from");
        $this->assertTrue($definition->hasOption('to'), "Missing argument to");
        $this->assertTrue($definition->hasOption('write-sql'));
        $this->assertTrue($definition->hasOption('type'));
    }

    /**
     * Data provider for testPerform
     *
     * @return array
     */
    public function executeProvider()
    {
        return [
            'Without --write-sql' => [
                'executeParams' => [],
                'testFile' => '/tmp/output_file.sql',
                'contains' => [],
                'expectedExceptionType' => 'InvalidArgumentException',
                'expectedExceptionMessage' => '--write-sql=path is required'
            ],
            'Without --type' => [
                'executeParams' => [
                    '--write-sql' => '/tmp/output_file.sql'
                ],
                'testFile' => '/tmp/output_file.sql',
                'contains' => [],
                'expectedExceptionType' => 'InvalidArgumentException',
                'expectedExceptionMessage' => '--type=mysql is required'
            ],
            'Invalid type' => [
                'executeParams' => [
                    '--write-sql' => '/tmp/output_file.sql',
                    '--type' => 'foo'
                ],
                'testFile' => '/tmp/output_file.sql',
                'contains' => [],
                'expectedExceptionType' => 'Exception',
                'expectedExceptionMessage' => 'non-existent service'
            ],
            'Successful mysql' => [
                'executeParams' => [
                    '--write-sql' => '/tmp/output_file.sql',
                    '--type' => 'mysql'
                ],
                'testFile' => '/tmp/output_file.sql',
                'contains' => [
                    'DROP TABLE IF EXISTS organization;',
                    'SOME SQL;',
                    'SOME MORE SQL;'
                ]
            ],
            'Successful Sqlite' => [
                'executeParams' => [
                    '--write-sql' => '/tmp/output_file.sql',
                    '--type' => 'sqlite'
                ],
                'testFile' => '/tmp/output_file.sql',
                'contains' => [
                    'DROP TABLE IF EXISTS organization;',
                    'SOME SQL;',
                    'SOME MORE SQL;'
                ]
            ],
            
        ];
    }
        
    /**
     * @dataProvider executeProvider
     */
    public function testExecute(
        $executeParams,
        $testFile = null,
        $contains = [],
        $expectedExceptionType = null,
        $expectedExceptionMessage = null
    ) {
        $container = $this->getContainer();
        if ($expectedExceptionType) {
            $this->setExpectedException($expectedExceptionType, $expectedExceptionMessage);
        }
        $commandTester = $this->provideCommandTester($container);
        $commandTester->execute($executeParams);
        if ($testFile) {
            $content = file_get_contents($testFile);
            unlink($testFile);
            foreach ($contains as $containString) {
                $this->assertContains($containString, $content);
            }
        }
    }
}

// @codingStandardsIgnoreStart
class ForcedMySQLPlatformConnection extends Connection
{
    public function getDatabasePlatform()
    {
        return new MySqlPlatform();
    }
}
// @codingStandardsIgnoreEnd
