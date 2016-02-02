<?php

namespace Tornado\Application;

use Symfony\Component\Console\Application as ConsoleApplication;

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
     */
    public function run()
    {
        $application = new ConsoleApplication();
        $commands = $this->getContainer()
            ->get('datasift.command.bag')
            ->getCommands();

        foreach ($commands as $command) {
            $application->add($command);
        }

        $application->run();
    }

    /**
     * Overwrites routes registering for this Application where it is unnecessary and redundant stuff
     */
    protected function registerRoutes()
    {
        return;
    }
}
