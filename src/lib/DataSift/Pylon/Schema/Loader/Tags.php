<?php

namespace DataSift\Pylon\Schema\Loader;

use DataSift_Pylon;

use Doctrine\Common\Cache\Cache;

use Tornado\Project\Recording;
use DataSift\Pylon\Schema\LoaderInterface;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use DataSift\Pylon\SubscriptionInterface;

/**
 * Fetches VEDO Classification tags and translates them to schema objects.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Pylon\Schema
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Tags implements LoggerAwareInterface, LoaderInterface
{

    use LoggerAwareTrait;

    const CACHE_KEY_PREFIX = 'datasift.tornado.recording_tags:';
    const CACHE_TTL = 3600;

    /**
     * DataSift Pylon API client.
     *
     * @var DataSift_Pylon
     */
    protected $pylon;

    /**
     * Cache.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param DataSift_Pylon    $pylon  DataSift Pylon API client.
     * @param Cache             $cache  Cache.
     */
    public function __construct(DataSift_Pylon $pylon, Cache $cache)
    {
        $this->pylon = $pylon;
        $this->cache = $cache;
    }

    /**
     * Fetches tags from the given recording.
     *
     * @param \DataSift\Pylon\SubscriptionInterface|null $subscription
     *
     * @return array
     */
    public function load(SubscriptionInterface $subscription = null)
    {
        if (!$subscription) {
            return [];
        }
        $hash = $subscription->getSubscriptionId();
        $cacheKey = self::CACHE_KEY_PREFIX . $hash;

        $tagObjects = $this->cache->fetch($cacheKey);
        if ($tagObjects !== false) {
            return $tagObjects;
        }

        $tags = $this->pylon->tags($hash);

        // convert the tags to objects
        $tagObjects = $this->convertTagsToObjects($tags);

        // store in cache and return
        $this->cache->save($cacheKey, $tagObjects, self::CACHE_TTL);
        return $tagObjects;
    }

    /**
     * Converts array of tag strings to dimension/schema objects.
     *
     * @param  array  $tags Array of tags as strings.
     * @return array
     */
    protected function convertTagsToObjects(array $tags)
    {
        $objects = [];
        foreach ($tags as $tag) {
            $objects[] = [
                'target' => $tag,
                'label' => $this->labelForTag($tag),
                'description' => 'VEDO Classification tag.',
                'is_analysable' => true,
                'vedo_tag' => true
            ];
        }

        return $objects;
    }

    /**
     * Creates a label from a tag.
     *
     * @param  string $tag
     * @return string
     */
    protected function labelForTag($tag)
    {
        $label = str_replace('interaction.tag_tree.', '', $tag);
        $label = str_replace(['.', '_'], ' ', $label);
        $label = ucwords($label);
        return $label;
    }
}
