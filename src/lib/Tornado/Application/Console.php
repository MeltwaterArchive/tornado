<?php

namespace Tornado\Application;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Command\MigrationsStatus;
use Command\MigrationsMigrate;
use Command\MigrationsMasterSql;
use DataSift\Silex\Application;

/**
 * Tornado Console Application config
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual propferty rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Application
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Console extends Application
{
    /**
     * Registers all CLI application commands based on the CommandBag service.
     *
     * Handles the cli stdin and delivers the cli stdout based on the Command data processing.
     * {@inheritdoc}
     */
    public function run(SymfonyRequest $request = null)
    {
        $container = $this->getContainer();

        $application = new ConsoleApplication();
        $application->setDispatcher($this['dispatcher']);

        $commands = $container
            ->get('datasift.command.bag')
            ->getCommands();

        foreach ($commands as $command) {
            $application->add($command);
        }

        $this->registerMigrations($application);

        $application->run();
    }

    /**
     * Overwrites routes registering for this Application where it is unnecessary and redundant stuff
     */
    protected function registerRoutes()
    {
        return;
    }

    /**
     * Registers the migration commands required for DB management
     *
     * @param \Symfony\Component\Console\Application $application
     */
    protected function registerMigrations(ConsoleApplication $application)
    {
        $container = $this->getContainer();

        $helpers = array('dialog' => new DialogHelper());
        $helperSet = new HelperSet($helpers);
        $application->setHelperSet($helperSet);

        $commands = [
            new MigrationsStatus($container),
            new MigrationsMigrate($container),
            new MigrationsMasterSql($container),
        ];

        foreach ($commands as $command) {
            $application->add($command);
        };
    }
}
