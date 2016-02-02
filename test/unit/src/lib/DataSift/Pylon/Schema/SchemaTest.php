<?php

namespace Test\DataSift\Pylon\Schema;

use DataSift\Pylon\Schema\Schema;

/**
 * SchemaTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\DataSift\Pylon\Schema
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \DataSift\Pylon\Schema\Schema
 */
class SchemaTest extends \PHPUnit_Framework_TestCase
{

    /**
     * DataProvider for getObjects
     *
     * @return array
     */
    public function getObjectsProvider()
    {
        return [
            [ // #0
                'schema' => $this->getSchemaObjects(),
                'keysToReturn' => ['target', 'cardinality'],
                'filter' => [],
                'permissions' => [],
                'expected' => [
                    'fb.author.id' => ['target' => 'fb.author.id'],
                    'fb.author.age' => ['target' => 'fb.author.age', 'cardinality' => 7],
                    'fb.author.gender' => ['target' => 'fb.author.gender'],
                    'fb.author.country' => ['target' => 'fb.author.country', 'cardinality' => 250],
                ]
            ],
            [ // #1
                'schema' => $this->getSchemaObjects(),
                'keysToReturn' => ['target', 'cardinality'],
                'filter' => ['is_analysable' => true],
                'permissions' => ['everyone'],
                'expected' => [
                    'fb.author.id' => ['target' => 'fb.author.id'],
                    'fb.author.gender' => ['target' => 'fb.author.gender'],
                ]
            ],
            [ // #2 - filter by permissions
                'schema' => $this->getSchemaObjects(),
                'keysToReturn' => ['target'],
                'filter' => ['is_analysable' => true],
                'permissions' => ['internal'],
                'expected' => [
                    'fb.author.id' => ['target' => 'fb.author.id'],
                    'fb.author.gender' => ['target' => 'fb.author.gender'],
                    'fb.author.relationship_status' => ['target' => 'fb.author.relationship_status'],
                    'fb.content.sentiment' => ['target' => 'fb.content.sentiment']
                ]
            ],
            [ // #3 - filter by permissions
                'schema' => $this->getSchemaObjects(),
                'keysToReturn' => ['target'],
                'filter' => ['is_analysable' => true],
                'permissions' => ['internal', 'premium'],
                'expected' => [
                    'fb.author.id' => ['target' => 'fb.author.id'],
                    'fb.author.gender' => ['target' => 'fb.author.gender'],
                    'fb.author.relationship_status' => ['target' => 'fb.author.relationship_status'],
                    'fb.content.sentiment' => ['target' => 'fb.content.sentiment'],
                    'fb.author.location' => ['target' => 'fb.author.location'],
                    'fb.author.education_level' => ['target' => 'fb.author.education_level'],
                ]
            ],
            [ // #4 - no keys to filter
                'schema' => $this->getSchemaObjects(),
                'keysToReturn' => [],
                'filter' => [],
                'permissions' => [],
                'expected' => [
                    'fb.author.id' => [
                        'target' => 'fb.author.id',
                        'is_mandatory' => true,
                        'is_analysable' => true,
                    ],
                    'fb.author.age' => [
                        'target' => 'fb.author.age',
                        'cardinality' => 7,
                        'description' => "One of '18-24'",
                        'is_analysable' => false,
                    ],
                    'fb.author.gender' => [
                        'target' => 'fb.author.gender',
                        'perms' => [],
                        'is_mandatory' => false,
                        'is_analysable' => true,
                    ],
                    'fb.author.country' => [
                        'target' => 'fb.author.country',
                        'cardinality' => 250,
                        'is_analysable' => false,
                    ],
                ]
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::getObjects
     * @covers ::filterObject
     * @covers ::hasPermissions
     *
     * @dataProvider getObjectsProvider
     *
     * @param array $schema
     * @param array $keysToReturn
     * @param array $filter
     * @param array $expected
     */
    public function testGetObjects(
        array $schema,
        array $keysToReturn,
        array $filter,
        array $permissions,
        array $expected
    ) {
        $schema = new Schema($schema);
        $this->assertEquals($expected, $schema->getObjects($keysToReturn, $filter, $permissions));
    }

    public function getTargetsProvider()
    {
        return [
            'No filters or permissions' => [
                'filter' => [],
                'permissions' => [],
                'expected' => [
                    'fb.author.id',
                    'fb.author.age',
                    'fb.author.gender',
                    'fb.author.country'
                ]
            ],
            'No filters or permissions' => [
                'filter' => [],
                'permissions' => ['internal'],
                'expected' => [
                    'fb.author.id',
                    'fb.author.age',
                    'fb.author.gender',
                    'fb.author.country',
                    'fb.author.relationship_status',
                    'fb.content.sentiment'
                ]
            ]
        ];
    }

    /**
     * @dataProvider getTargetsProvider
     *
     * @covers ::__construct
     * @covers ::getTargets
     */
    public function testGettingTargets(array $filter, array $permissions, array $expected)
    {
        $schema = new Schema($this->getSchemaObjects());

        $this->assertEquals($expected, $schema->getTargets($filter, $permissions));
    }

    /**
     * @covers ::__construct
     * @covers ::findObjectByTarget
     */
    public function testFindingObjectByTarget()
    {
        $schema = new Schema($this->getSchemaObjects());

        $this->assertEquals(
            [
                'target' => 'fb.author.id',
                'is_mandatory' => true,
                'is_analysable' => true
            ],
            $schema->findObjectByTarget('fb.author.id')
        );

        $this->assertEquals(null, $schema->findObjectByTarget('fb'));
    }

    /**
     * @covers ::__construct
     * @covers ::findObjectByTarget
     */
    public function testRemovingDuplicatedObjectsTargets()
    {
        $schema = new Schema($this->getSchemaObjects());

        $this->assertNotEquals(
            [
                'target' => 'fb.author.country',
                'cardinality' => 249,
                'is_analysable' => false
            ],
            $schema->findObjectByTarget('fb.author.country')
        );
        $this->assertEquals(
            [
                'target' => 'fb.author.country',
                'cardinality' => 250,
                'is_analysable' => false
            ],
            $schema->findObjectByTarget('fb.author.country')
        );
    }

    /**
     * Provides Schema objects
     *
     * @return array
     */
    protected function getSchemaObjects()
    {
        return [
            'fb.author.id' => [
                'target' => 'fb.author.id',
                'is_mandatory' => true,
                'is_analysable' => true,
            ],
            'fb.author.age' => [
                'target' => 'fb.author.age',
                'cardinality' => 7,
                'description' => "One of '18-24'",
                'is_analysable' => false,
            ],
            'fb.author.gender' => [
                'target' => 'fb.author.gender',
                'perms' => [],
                'is_mandatory' => false,
                'is_analysable' => true,
            ],
            'fb.author.country' => [
                'target' => 'fb.author.country',
                'cardinality' => 249,
                'is_analysable' => false,
            ],
            'fb.author.country' => [
                'target' => 'fb.author.country',
                'cardinality' => 250,
                'is_analysable' => false,
            ],
            'fb.author.location' => [
                'target' => 'fb.author.location',
                'cardinality' => 249,
                'is_analysable' => true,
                'perms' => ['premium']
            ],
            'fb.author.relationship_status' => [
                'target' => 'fb.author.relationship_status',
                'cardinality' => 5,
                'is_analysable' => true,
                'perms' => ['internal']
            ],
            'fb.content.sentiment' => [
                'target' => 'fb.content.sentiment',
                'is_analysable' => true,
                'perms' => ['premium', 'internal']
            ],
            'fb.author.education_level' => [
                'target' => 'fb.author.education_level',
                'cardinality' => 7,
                'is_analysable' => true,
                'perms' => ['premium']
            ],
        ];
    }

    /**
     * This is a special case test for https://jiradatasift.atlassian.net/browse/NEV-257 where
     * filtering behaves weird and I can't reproduce it on the fixtures above.
     */
    public function testFilteringRealData()
    {
        $contents = file_get_contents(__DIR__ .'/../../../../../../../src/config/pylon/fb_pylon_schema.json');
        $data = json_decode($contents, true);
        $schema = new Schema($data['schema']);

        foreach ($data['schema'] as $object) {
            // make sure the permissions are correct to retrieve it
            $perms = isset($object['perms']) ? $object['perms'] : [];

            $this->assertEquals(
                $object,
                $schema->findObjectByTarget($object['target'], $perms),
                sprintf('Failed to find an existing object %s', $object['target'])
            );
        }
    }
}
