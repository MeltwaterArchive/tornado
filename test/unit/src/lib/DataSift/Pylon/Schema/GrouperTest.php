<?php

namespace Test\DataSift\Pylon\Schema;

use Mockery;

use DataSift\Pylon\Schema\Grouper;

/**
 * GrouperTest
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
 * @covers \DataSift\Pylon\Schema\Grouper
 */
class GrouperTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @dataProvider provideObjectsToGroup
     *
     * @param  array  $objects
     * @param  array  $groups
     * @param  array  $expected
     */
    public function testGroupObjects(array $objects, array $groups, array $expected)
    {
        $loader = Mockery::mock('DataSift\Loader\LoaderInterface');
        $loader->shouldReceive('load')
            ->andReturn(['/tmp/groups.json' => ['groups' => $groups]])
            ->once();

        $grouper = new Grouper($loader);
        $result = $grouper->groupObjects($objects);
        $this->assertEquals($expected, $result);
    }

    public function testNotLoadingTwice()
    {
        $loader = Mockery::mock('DataSift\Loader\LoaderInterface');
        $loader->shouldReceive('load')
            ->andReturn(['/tmp/groups.json' => ['groups' => []]])
            ->once();

        $grouper = new Grouper($loader);
        $grouper->groupObjects([]);
        // this 2nd call shouldn't trigger `LoaderInterface::load()`
        $grouper->groupObjects([]);
    }

    /**
     * Data provider for `::testGroupObjects`.
     *
     * @return array
     */
    public function provideObjectsToGroup()
    {
        $objects = [];
        foreach ([
            'fb.author.id',
            'fb.author.age',
            'fb.author.country',
            'fb.author.gender',
            'fb.language',
            'fb.links',
            'interaction.content',
            'interaction.tag_tree',
            'fb.story.content'
        ] as $target) {
            $objects[$target] = ['target' => $target];
        }

        $tags = [];
        foreach ([
            'interaction.tag_tree.lorem.ipsum',
            'interaction.tag_tree.lorem.dolor',
            'interaction.tag_tree.sit_amet'
        ] as $tag) {
            $tags[$tag] = ['target' => $tag, 'vedo_tag' => true];
        }

        $objectsWithTags = array_merge($objects, $tags);

        return [
            [ // #0 - empty
                'objects' => [],
                'groups' => [],
                'expected' => []
            ],
            [ // #1 - empty groups
                'objects' => $objects,
                'groups' => [],
                'expected' => []
            ],
            [ // #2 - empty objects
                'objects' => [],
                'groups' => [
                    [
                        'name' => 'Author',
                        'targets' => [
                            'fb.author.id',
                            'fb.author.age',
                            'fb.author.country',
                            'fb.author.gender'
                        ]
                    ],
                    [
                        'name' => 'Meta',
                        'targets' => [
                            'fb.language',
                            'fb.links'
                        ]
                    ],
                    [
                        'name' => 'Content',
                        'targets' => [
                            'interaction.content',
                            'fb.story.content'
                        ]
                    ]
                ],
                'expected' => []
            ],
            [ // #3 - groups with no targets list
                'objects' => $objects,
                'groups' => [
                    ['name' => 'Author'],
                    ['name' => 'Meta']
                ],
                'expected' => []
            ],
            [ // #3 - simple groups
                'objects' => $objects,
                'groups' => [
                    [
                        'name' => 'Author',
                        'targets' => [
                            'fb.author.id',
                            'fb.author.age',
                            'fb.author.country',
                            'fb.author.gender'
                        ]
                    ],
                    [
                        'name' => 'Meta',
                        'targets' => [
                            'fb.language',
                            'fb.links'
                        ]
                    ],
                    [
                        'name' => 'Content',
                        'targets' => [
                            'interaction.content',
                            'fb.story.content'
                        ]
                    ]
                ],
                'expected' => [
                    [
                        'name' => 'Author',
                        'items' => [
                            ['target' => 'fb.author.id'],
                            ['target' => 'fb.author.age'],
                            ['target' => 'fb.author.country'],
                            ['target' => 'fb.author.gender']
                        ]
                    ],
                    [
                        'name' => 'Meta',
                        'items' => [
                            ['target' => 'fb.language'],
                            ['target' => 'fb.links']
                        ]
                    ],
                    [
                        'name' => 'Content',
                        'items' => [
                            ['target' => 'interaction.content'],
                            ['target' => 'fb.story.content']
                        ]
                    ]
                ]
            ],
            [ // #4 - catch all group
                'objects' => $objects,
                'groups' => [
                    [
                        'name' => 'Author',
                        'targets' => [
                            'fb.author.id',
                            'fb.author.age',
                            'fb.author.country',
                            'fb.author.gender'
                        ]
                    ],
                    [
                        'name' => 'Other',
                        'special' => 'catchall'
                    ]
                ],
                'expected' => [
                    [
                        'name' => 'Author',
                        'items' => [
                            ['target' => 'fb.author.id'],
                            ['target' => 'fb.author.age'],
                            ['target' => 'fb.author.country'],
                            ['target' => 'fb.author.gender']
                        ]
                    ],
                    [
                        'name' => 'Other',
                        'items' => [
                            ['target' => 'fb.language'],
                            ['target' => 'fb.links'],
                            ['target' => 'interaction.content'],
                            ['target' => 'interaction.tag_tree'],
                            ['target' => 'fb.story.content']
                        ]
                    ]
                ]
            ],
            [ // #5 - vedo tags group
                'objects' => $objectsWithTags,
                'groups' => [
                    [
                        'name' => 'Author',
                        'targets' => [
                            'fb.author.id',
                            'fb.author.age',
                            'fb.author.country',
                            'fb.author.gender'
                        ]
                    ],
                    [
                        'name' => 'Tags',
                        'special' => 'vedo_tags'
                    ]
                ],
                'expected' => [
                    [
                        'name' => 'Author',
                        'items' => [
                            ['target' => 'fb.author.id'],
                            ['target' => 'fb.author.age'],
                            ['target' => 'fb.author.country'],
                            ['target' => 'fb.author.gender']
                        ]
                    ],
                    [
                        'name' => 'Tags',
                        'items' => [
                            ['target' => 'interaction.tag_tree.lorem.ipsum', 'vedo_tag' => true],
                            ['target' => 'interaction.tag_tree.lorem.dolor', 'vedo_tag' => true],
                            ['target' => 'interaction.tag_tree.sit_amet', 'vedo_tag' => true],
                        ]
                    ]
                ]
            ],
            [ // #6 - vedo tags group with specified items
                'objects' => $objectsWithTags,
                'groups' => [
                    [
                        'name' => 'Author',
                        'targets' => [
                            'fb.author.id',
                            'fb.author.age',
                            'fb.author.country',
                            'fb.author.gender'
                        ]
                    ],
                    [
                        'name' => 'Tags',
                        'special' => 'vedo_tags',
                        'targets' => [
                            'interaction.tag_tree'
                        ]
                    ]
                ],
                'expected' => [
                    [
                        'name' => 'Author',
                        'items' => [
                            ['target' => 'fb.author.id'],
                            ['target' => 'fb.author.age'],
                            ['target' => 'fb.author.country'],
                            ['target' => 'fb.author.gender']
                        ]
                    ],
                    [
                        'name' => 'Tags',
                        'items' => [
                            ['target' => 'interaction.tag_tree'],
                            ['target' => 'interaction.tag_tree.lorem.ipsum', 'vedo_tag' => true],
                            ['target' => 'interaction.tag_tree.lorem.dolor', 'vedo_tag' => true],
                            ['target' => 'interaction.tag_tree.sit_amet', 'vedo_tag' => true],
                        ]
                    ]
                ]
            ],
            [ // #6 - vedo tags group with specified items and a catch all group
                'objects' => $objectsWithTags,
                'groups' => [
                    [
                        'name' => 'Author',
                        'targets' => [
                            'fb.author.id',
                            'fb.author.age',
                            'fb.author.country',
                            'fb.author.gender'
                        ]
                    ],
                    [
                        'name' => 'Tags',
                        'special' => 'vedo_tags',
                        'targets' => [
                            'interaction.tag_tree'
                        ]
                    ],
                    [
                        'name' => 'Other',
                        'special' => 'catchall'
                    ]
                ],
                'expected' => [
                    [
                        'name' => 'Author',
                        'items' => [
                            ['target' => 'fb.author.id'],
                            ['target' => 'fb.author.age'],
                            ['target' => 'fb.author.country'],
                            ['target' => 'fb.author.gender']
                        ]
                    ],
                    [
                        'name' => 'Tags',
                        'items' => [
                            ['target' => 'interaction.tag_tree'],
                            ['target' => 'interaction.tag_tree.lorem.ipsum', 'vedo_tag' => true],
                            ['target' => 'interaction.tag_tree.lorem.dolor', 'vedo_tag' => true],
                            ['target' => 'interaction.tag_tree.sit_amet', 'vedo_tag' => true],
                        ]
                    ],
                    [
                        'name' => 'Other',
                        'items' => [
                            ['target' => 'fb.language'],
                            ['target' => 'fb.links'],
                            ['target' => 'interaction.content'],
                            ['target' => 'fb.story.content']
                        ]
                    ]
                ]
            ],
        ];
    }
}
