<?php

namespace Command;

use Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
class MigrationsMigrate extends MigrateCommand
{
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
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setMigrationConfiguration($this->getMigrationsConfig($this->container));
        parent::execute($input, $output);
    }
}
