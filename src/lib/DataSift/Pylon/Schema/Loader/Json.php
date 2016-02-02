<?php

namespace DataSift\Pylon\Schema\Loader;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use DataSift\Loader\Json as BaseJsonLoader;
use DataSift\Pylon\Schema\LoaderInterface;
use DataSift\Pylon\SubscriptionInterface;

/**
 * Json loader for Pylon schema definition stored in json files
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Pylon\Schema\Loader
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Json extends BaseJsonLoader implements LoggerAwareInterface, LoaderInterface
{
    use LoggerAwareTrait;

    /**
     * @param string[]                 $paths
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct($paths = [], LoggerInterface $logger = null)
    {
        parent::__construct($paths);
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Checks if schema has been defined in json file based on its parsed representation.
     * If it is, extracts and merges all of them and returns.
     *
     * {@inheritdoc}
     */
    public function load(SubscriptionInterface $subscription = null)
    {
        $filesContent = parent::load();

        $schema = [];
        foreach ($filesContent as $path => $parsedContent) {
            if (!isset($parsedContent['schema'])) {
                $this->logger->alert(sprintf(
                    '%s: Pylon Schema file "%s" does not contain required "schema" key.',
                    __METHOD__,
                    $path
                ));

                continue;
            }

            $schema = array_merge($schema, $parsedContent['schema']);
        }

        return $schema;
    }
}
