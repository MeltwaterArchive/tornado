<?php

namespace Command;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\DBAL\Migrations\Configuration\Configuration;

/**
 * MigrationsTrait
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Command
 * @author      Ollie Parsley <ollie@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
trait MigrationsTrait
{
    /**
     * Get a config for use with migration commands
     *
     * @param ContainerInterface $container
     *
     * @return Configuration
     */
    protected function getMigrationsConfig(ContainerInterface $container, $dummy = false)
    {
        $conn = $dummy ? 'doctrine.dbal.connection.dummy.sqlite' : 'doctrine.dbal.connection';
        $config = new Configuration($container->get($conn));
        $config->setMigrationsNamespace('DoctrineMigrations');
        $config->setMigrationsDirectory($container->getParameter('db.migrations.path'));
        $config->registerMigrationsFromDirectory($container->getParameter('db.migrations.path'));
        return $config;
    }
}
