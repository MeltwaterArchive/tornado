<?php

namespace DataSift\Silex;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * DataSift Silex Application Bootstrap.
 *
 * Responsible for building the DIC and Application instance.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Bootstrap
{
    /**
     * @var string
     */
    protected $configurationPath;

    /**
     * @var string
     */
    protected $resourcesPath;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var boolean
     */
    protected $debug;

    /**
     * @var CompilerPassInterface[]
     */
    protected $compilerPasses = [];

    /**
     * Constructor.
     *
     * @param string  $configurationPath
     * @param string  $resourcesPath
     * @param string  $environment
     * @param boolean $debug Default: `false`.
     */
    public function __construct($configurationPath, $resourcesPath, $environment, $debug = false)
    {
        $this->configurationPath = $configurationPath;
        $this->resourcesPath = $resourcesPath;
        $this->environment = $environment;
        $this->debug = (bool)$debug;
    }

    /**
     * @return string
     */
    public function getConfigurationPath()
    {
        return $this->configurationPath;
    }

    /**
     * @return string
     */
    public function getResourcesPath()
    {
        return $this->resourcesPath;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return strtolower($this->environment);
    }

    /**
     * @return boolean
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * Builds a DIC
     *
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function buildContainer()
    {
        $container = new ContainerBuilder();

        // set default values for some params (but they can (and should) be overwritten in parameters.yml)
        $container->setParameter('env', $this->getEnvironment());
        $container->setParameter('debug', $this->isDebug());
        $container->setParameter('root_dir', realpath(__DIR__ . '/../../..'));
        $container->setParameter('config_dir', $this->getConfigurationPath());
        $container->setParameter('resources_dir', $this->getResourcesPath());

        $loader = new YamlFileLoader($container, new FileLocator($this->getConfigurationPath()));
        $loader->load('services.yml');

        $search = [
            "{$this->getResourcesPath()}/parameters.yml",
            "{$this->getResourcesPath()}/{$this->getEnvironment()}/parameters.yml",
        ];

        $loaded = false;
        foreach ($search as $filename) {
            if (file_exists($filename)) {
                $loader->load($filename);
                $loaded = true;
                break;
            }
        }

        if (!$loaded) {
            throw new \InvalidArgumentException('Could not find configuration; searched: ' . implode(', ', $search));
        }

        $this->registerCompilerPass($container);

        $container->compile();

        return $container;
    }

    /**
     * Creates the Application instance
     *
     * @param string $applicationClass
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *
     * @return \DataSift\Silex\Application
     */
    public function createApplication($applicationClass, ContainerInterface $container)
    {
        return new $applicationClass($container, ['env' => $this->getEnvironment()]);
    }

    /**
     * Adds CompilerPass to the Compiler Pass collection
     *
     * @param \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface $compilerPass
     */
    public function addCompilerPass(CompilerPassInterface $compilerPass)
    {
        $this->compilerPasses[] = $compilerPass;
    }

    /**
     * Registers Container compiler passes
     *
     * @param ContainerBuilder $containerBuilder
     */
    protected function registerCompilerPass(ContainerBuilder $containerBuilder)
    {
        foreach ($this->compilerPasses as $compilerPass) {
            $containerBuilder->addCompilerPass($compilerPass);
        }
    }
}
