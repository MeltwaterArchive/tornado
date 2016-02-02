<?php

namespace DataSift\Pylon\Schema;

use MD\Foundation\Utils\ArrayUtils;

use DataSift\Loader\LoaderInterface as DLoaderInterface;

/**
 * Groups dimensions/targets.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Pylon
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Grouper
{
    /**
     * Data loader.
     *
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * Has the groups definition been loaded yet?
     *
     * @var boolean
     */
    protected $loaded = false;

    /**
     * Groups definition.
     *
     * @var array
     */
    protected $groups = [];

    /**
     * Constructor.
     *
     * @param LoaderInterface $loader Data loader.
     */
    public function __construct(DLoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Loads groups definition.
     *
     * @return array
     */
    protected function load()
    {
        if ($this->loaded) {
            return;
        }

        $data = $this->loader->load();
        $this->groups = current($data)['groups'];
        $this->loaded = true;
        return $this->groups;
    }

    /**
     * Group objects.
     *
     * @param  array  $objects Target/dimension objects, usually obtained by calling
     *                         `Schema::getObjects()`.
     * @return array
     */
    public function groupObjects(array $objects)
    {
        $this->load();

        // make sure objects are indexed by target name for easy lookup
        $objects = ArrayUtils::indexBy($objects, 'target');

        $groups = [];
        foreach ($this->groups as $groupDefinition) {
            $items = $this->pullObjectsToGroup($groupDefinition, $objects);
            if (count($items)) {
                $groups[] = [
                    'name' => $groupDefinition['name'],
                    'items' => $items
                ];
            }
        }

        return $groups;
    }

    /**
     * Pulls objects from the objects array and puts them in a separate array.
     *
     * @param  array  $group    Group definition.
     * @param  array  &$objects Array of all objects. Passed by reference.
     * @return array
     */
    protected function pullObjectsToGroup(array $group, array &$objects)
    {
        // set defaults
        $group = array_merge([
            'name' => '',
            'targets' => [],
            'special' => null
        ], $group);

        $items = [];

        // pull any defined targets
        foreach ($group['targets'] as $target) {
            if (isset($objects[$target])) {
                $items[] = $objects[$target];
                // remove from the original array
                unset($objects[$target]);
            }
        }

        // handle special groups
        switch ($group['special']) {
            case 'vedo_tags':
                // put all vedo tags in this group
                foreach ($objects as $target => $object) {
                    if (isset($object['vedo_tag']) && $object['vedo_tag']) {
                        $items[] = $object;
                        unset($objects[$target]);
                    }
                }
                break;

            case 'catchall':
                // put all the remaining objects in this group
                $items = array_merge(
                    $items,
                    array_values($objects)
                );
                $objects = []; // this is now empty
                break;
        }

        return $items;
    }
}
