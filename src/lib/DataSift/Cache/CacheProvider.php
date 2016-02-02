<?php

namespace DataSift\Cache;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Cache\Cache;

/**
 * Provides a cache service based on passed parameters.
 *
 * This class is used as a factory for `@cache` service and it allows to
 * dynamically configure what type of cache is used by the application,
 * based on the passed `$cacheServiceName`.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Cache
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class CacheProvider
{
    /**
     * Dependency injection container.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Name of the service that should be used as a general `@cache` service.
     *
     * @var string
     */
    protected $cacheServiceName;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container        Dependency injection container.
     * @param string             $cacheServiceName Name of the service that this provider should return.
     */
    public function __construct(ContainerInterface $container, $cacheServiceName)
    {
        $this->container = $container;
        $this->cacheServiceName = $cacheServiceName;
    }

    /**
     * Provides a cache service.
     *
     * @return Cache
     *
     * @throws \RuntimeException When the defined cache service does not implement Cache interface.
     */
    public function provideCache()
    {
        $cache = $this->container->get($this->cacheServiceName);

        if (!$cache instanceof Cache) {
            throw new \RuntimeException(sprintf(
                'Cache service "%s" must implement %s interface.',
                $this->cacheServiceName,
                'Doctrine\Common\Cache\Cache'
            ));
        }

        return $cache;
    }
}
