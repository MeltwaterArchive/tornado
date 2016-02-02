<?php

namespace Test\DataSift;

use Symfony\Component\DependencyInjection\ContainerInterface;

use DataSift\Silex\Bootstrap;
use Tornado\Application\Tornado;

/**
 * ApplicationBuilder trait which builds an application container
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\DataSift
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
trait ApplicationBuilder
{
    /**
     * Root dir of the project.
     *
     * @var string
     */
    protected $rootDir;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Bootstraps the Tornado application instance
     *
     * @param string $applicationClass
     * @param string $configPath
     *
     * @return void
     */
    public function buildApplication($applicationClass = Tornado::class, $configPath = '/src/config/tornado')
    {
        $this->rootDir = realpath(__DIR__ .'/../../../../..');
        $bootstrap = new Bootstrap(
            $this->rootDir . $configPath,
            realpath($this->rootDir .'/resources/config') ?: '/etc/ms-app-tornado',
            'test'
        );

        // Build the container
        $this->container = $bootstrap->buildContainer();
        $bootstrap->createApplication($applicationClass, $this->container);
    }
}
