<?php

namespace Tornado\Analyze\Dimension;

use DataSift\Pylon\Schema\Schema;
use DataSift\Pylon\Schema\Provider;
use DataSift\Pylon\SubscriptionInterface;

use Tornado\Analyze\Dimension;

/**
 * Factory class for creating the Dimension objects based on the given Schema definition
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Analyze\Dimension
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Factory
{
    /**
     * @var \DataSift\Pylon\Schema\Schema
     */
    protected $schemaProvider;

    public function __construct(Provider $schemaProvider)
    {
        $this->schemaProvider = $schemaProvider;
    }

    /**
     * Creates the DimensionCollection object based on the given dimensions object list
     *
     * @param array $dimensions list dimension object with target and threshold
     *
     * @return \Tornado\Analyze\Dimension\Collection
     */
    public function getDimensionCollection(
        array $dimensions,
        SubscriptionInterface $subscription = null,
        $targetPermissions = []
    ) {
        $dimensionCollection = new Collection();
        $schema = $this->schemaProvider->getSchema($subscription);

        foreach ($dimensions as $index => $dimension) {
            if (!isset($dimension['target'])) {
                throw new \InvalidArgumentException(sprintf(
                    '%s "target" is required for any dimension object in the list. Failed object index %d.',
                    __METHOD__,
                    $index
                ));
            }

            $dimensionCollection->addDimension($this->getDimensionDefinition($dimension, $schema, $targetPermissions));
        }

        return $dimensionCollection;
    }

    /**
     * Creates and configures the single Dimension object based on the given target
     *
     * @param array $dimension with target and optionally threshold keys
     *
     * @return \Tornado\Analyze\Dimension
     */
    protected function getDimensionDefinition(array $dimension, Schema $schema, $permissions = [])
    {
        $target = $dimension['target'];
        $dimensionDef = $schema->findObjectByTarget($target, $permissions);
        if (!$dimensionDef) {
            throw new \InvalidArgumentException(sprintf(
                '%s has not found any definition for target "%s" in schema.',
                __METHOD__,
                $target
            ));
        }

        $dimensionObj = new Dimension($target);
        if (isset($dimensionDef['cardinality'])) {
            $dimensionObj->setCardinality((int)$dimensionDef['cardinality']);
        }

        if (isset($dimensionDef['label'])) {
            $dimensionObj->setLabel($dimensionDef['label']);
        }

        if (isset($dimension['threshold']) && is_int($dimension['threshold'])) {
            $dimensionObj->setThreshold((int)$dimension['threshold']);

            if (isset($dimensionDef['cardinality']) && $dimension['threshold'] > $dimensionDef['cardinality']) {
                $dimensionObj->setThreshold((int)$dimensionDef['cardinality']);
            }
        }

        return $dimensionObj;
    }
}
