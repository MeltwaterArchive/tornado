<?php

namespace DataSift\Silex;

use Monolog\Logger;

use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use SilexMemcache\MemcacheExtension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\KernelEvents;

use Doctrine\Common\Cache\MemcachedCache;

use DataSift\Http\Request;
use DataSift\Pylon\Schema;

/**
 * Basic Application class with common configuration for child classes.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift
 * @author      Michael Heap <michael.heap@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class Application extends \Silex\Application
{
    /**
     * @var ContainerInterface
     */
    protected $rootContainer;

    /**
     * Instantiate the application.
     *
     * @param ContainerInterface $container The root container of the application (if any)
     * @param array              $values    The parameters or objects.
     */
    public function __construct($container, array $values = [])
    {
        $this->rootContainer = $container;

        parent::__construct($values);

        $this['env'] = $container->getParameter('env');
        $this['debug'] = $container->getParameter('debug');

        $this->registerStatsCollectors();
        $this->registerRoutes();
        $this->registerProviders();
    }

    /**
     * @return boolean
     */
    public function isDebug()
    {
        return $this['debug'];
    }

    /**
     * Gets this Application running environment
     */
    public function getEnvironment()
    {
        return $this['env'];
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function getContainer()
    {
        return $this->rootContainer;
    }

    /**
     * @return RouteCollection
     */
    public function getRoutes()
    {
        return $this['routes'];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($identifier)
    {
        $inPimple = parent::offsetExists($identifier);
        if ($inPimple) {
            return parent::offsetGet($identifier);
        }

        return $this->rootContainer->get($identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($identifier)
    {
        if (parent::offsetExists($identifier)) {
            return true;
        }

        return $this->rootContainer->has($identifier);
    }

    /**
     * Registers stats collectors.
     */
    protected function registerStatsCollectors()
    {
        $container = $this->getContainer();

        // stat request as soon as possible (high priority)
        $this->on(KernelEvents::REQUEST, function ($event) use ($container) {
            $container->get('stats.http_collector')->onRequest($event);
        }, 999999);

        // stat response as late as possible (low priority)
        $this->on(KernelEvents::RESPONSE, function ($event) use ($container) {
            $container->get('stats.http_collector')->onResponse($event);
            $container->get('stats')->forceFlush();
        }, -999999);

        $this->on(KernelEvents::EXCEPTION, function ($event) use ($container) {
            $container->get('stats.http_collector')->onException($event);
        });

        // terminate is triggered in console
        $this->on(KernelEvents::TERMINATE, function ($event) use ($container) {
            $container->get('stats')->forceFlush();
        });
    }

    /**
     * Loads this Application routes.
     */
    protected function registerRoutes()
    {
        $container = $this->getContainer();
        $loader = new YamlFileLoader(new FileLocator($container->getParameter('routes.path')));
        $routesConfig = $loader->load('routes.yml');

        $routes = new RouteCollection();
        $routes->addCollection($routesConfig);

        $this->extend('routes', function () use ($routes) {
            return $routes;
        });
    }

    /**
     * Registers this Application providers.
     */
    protected function registerProviders()
    {
        $container = $this->getContainer();

        // monolog
        $this->register(new MonologServiceProvider(), [
            'monolog.logfile' => $container->getParameter('monolog.log_file'),
            'monolog.level' => $container->hasParameter('monolog.level') ?
                $container->getParameter('monolog.level') : Logger::DEBUG,
            'monolog.bubble' => $container->hasParameter('monolog.bubble') ?
                $container->getParameter('monolog.bubble') : true,
            'monolog.name' => $container->hasParameter('monolog.name') ?
                $container->getParameter('monolog.name') : 'tornado'
        ]);
        $container->set('monolog', $this['monolog']);

        // service controller
        $this->register(new ServiceControllerServiceProvider());

        // memcached
        $container->set('memcache.server', [$container->getParameter('memcache.server')]);
        $this->register(new MemcacheExtension(), $container->getParameter('memcache.server'));
        $memcached = new MemcachedCache();
        $memcached->setMemcached($this['memcache']);
        $memcached->setNamespace(
            $container->getParameter('tornado.cache.namespace') . ':' . $container->getParameter('env')
        );
        $container->set('cache.memcached', $memcached);

        // url generator
        $this->register(new UrlGeneratorServiceProvider());
        $container->set('url_generator', $this['url_generator']);

        // event dispatcher
        $container->set('event_dispatcher', $this['dispatcher']);

        $this->registerDoctrine();

        // validator
        $this->register(new ValidatorServiceProvider());
        $container->set('validator', $this['validator']);
    }

    /**
     * Overloads the parent::run for unified access to the application/json body params,
     * x-www-form-urlencoded params.
     *
     * {@inheritdoc}
     */
    public function run(SymfonyRequest $request = null)
    {
        if (null === $request) {
            $request = Request::createFromGlobals();
        }

        $response = $this->handle($request);
        $response->send();
        $this->terminate($request, $response);
    }

    /**
     * Registers the Doctrine provider
     */
    private function registerDoctrine()
    {
        $container = $this->getContainer();
        if (!($container->hasParameter('no_db') && $container->getParameter('no_db'))) {
            $driver = ($container->hasParameter('db.driver'))
                        ? $container->getParameter('db.driver')
                        : 'doctrine.dbal.connection.mysql';
            $container->set('doctrine.dbal.connection', $container->get($driver));
        }
    }
}
