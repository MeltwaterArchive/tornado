<?php

namespace DataSift\Command;

use Symfony\Component\Console\Command\Command;

/**
 * CommandBag
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Command
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class CommandBag
{
    /**
     * @var \Symfony\Component\Console\Command\Command[]
     */
    protected $commands = [];

    /**
     * Adds Command object to the Command list
     *
     * @param Command $command
     */
    public function addCommand(Command $command)
    {
        $this->commands[] = $command;
    }

    /**
     * Gets the list of registered command
     *
     * @return Command[]
     */
    public function getCommands()
    {
        return $this->commands;
    }
}
