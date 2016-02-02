<?php

namespace DataSift\Pylon\Schema;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;

use Doctrine\Common\Cache\Cache;
use DataSift\Pylon\Schema\LoaderInterface;
use DataSift\Pylon\SubscriptionInterface;

/**
 * Provider provides the Pylon schema definitions from different schema loaders
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Pylon\Schema
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Provider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const PYLON_SCHEMA_CACHE_ID = 'datasift.pylon.schema';

    /**
     * This Pylon final schema objects where its array key is equal the object definition target value
     * For instance: [
     *  'fb.author.id' => [
     *      'target' => 'fb.author.id',
     *      'perms' => [],
     *      'is_mandatory' => true
     *  ]
     * ]
     * @var array
     */
    protected $schemaObjects = [];

    /**
     * This Pylon schema definition cache client
     *
     * @var Cache
     */
    protected $cacheClient;

    /**
     * This Pylon schema definition loaders
     *
     * @var LoaderInterface[]
     */
    protected $loaders = [];

    /**
     * Validates given Pylon schema definition loaders
     *
     * @param LoaderInterface[]             $loaders
     * @param \Doctrine\Common\Cache\Cache  $cacheClient
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(array $loaders, Cache $cacheClient, LoggerInterface $logger = null)
    {
        $this->cacheClient = $cacheClient;
        $this->logger = $logger ?: new NullLogger();

        if (!count($loaders)) {
            throw new \InvalidArgumentException(sprintf(
                '%s expects at least one %s loader.',
                __METHOD__,
                '\DataSift\Loader\LoaderInterface'
            ));
        }

        foreach ($loaders as $loader) {
            $this->addLoader($loader);
        }
    }

    /**
     * Adds a loader to this Provider
     *
     * @param \DataSift\Pylon\Schema\LoaderInterface $loader
     */
    public function addLoader(LoaderInterface $loader)
    {
        $this->loaders[] = $loader;
    }

    /**
     * Returns Pylon Schema instance
     *
     * @param \DataSift\Pylon\SubscriptionInterface|null $subscription
     *
     * @return \DataSift\Pylon\Schema\Schema
     */
    public function getSchema(SubscriptionInterface $subscription = null)
    {
        if (!$this->schemaObjects) {
            $this->load($subscription);
        }
        return new Schema($this->schemaObjects, $this->logger);
    }

    /**
     * Reloads the final Pylon schema definitions objects
     *
     * Especially useful for cache invalidation.
     *
     * @param \DataSift\Pylon\SubscriptionInterface|null $subscription
     */
    public function reload(SubscriptionInterface $subscription = null)
    {
        $this->cacheClient->delete($this->getCacheId($subscription));
        $this->schemaObjects = [];
        $this->load($subscription);
    }

    /**
     * Loads the final pylon schema definition objects based on the multiple different schema loaders
     *
     * If pylon schema already exists in the cache storage, loads it directly from there
     *
     * @param \DataSift\Pylon\SubscriptionInterface|null $subscription
     */
    protected function load(SubscriptionInterface $subscription = null)
    {
        $cacheId = $this->getCacheId($subscription);

        if ($this->cacheClient->contains($cacheId)) {
            $this->schemaObjects = $this->cacheClient->fetch($cacheId);
        } else {
            $this->readLoaders($subscription);
            $this->cacheClient->save($cacheId, $this->schemaObjects);
        }
    }

    /**
     * Reads and merges schema definitions from multiple loaders based on the following steps:
     *
     * 1. loads definitions
     * 2. checks if definition has required "target" data
     *  2.1. if no -> process next definition
     *  2.2. if so -> continue processing
     * 3. checks if definition with given target has been already processed
     *  3.1. if no -> add it to the schemaObjects
     *  3.2. if so -> merge new target data with existing one
     *
     * @param \DataSift\Pylon\SubscriptionInterface|null $subscription
     */
    protected function readLoaders(SubscriptionInterface $subscription = null)
    {
        foreach ($this->loaders as $loader) {
            $definitions = $loader->load($subscription);

            foreach ($definitions as $definition) {
                if (!isset($definition['target'])) {
                    $this->logger->alert(sprintf(
                        '%s: Pylon Schema definition object does not contain required "target" data. Object: "%s".',
                        __METHOD__,
                        json_encode($definition)
                    ));

                    continue;
                }

                $target = $definition['target'];
                if (isset($this->schemaObjects[$target])) {
                    $this->schemaObjects[$target] = array_merge($this->schemaObjects[$target], $definition);
                } else {
                    $this->schemaObjects[$target] = $definition;
                }
            }
        }
    }

    /**
     * Returns this Pylon schema definition cache identifier
     *
     * @param SubscriptionInterface|null $subscription
     *
     * @return string
     */
    protected function getCacheId(SubscriptionInterface $subscription = null)
    {
        $cacheId = self::PYLON_SCHEMA_CACHE_ID;

        if ($subscription) {
            $cacheId .= '.' . $subscription->getHash();
        }

        return $cacheId;
    }
}
