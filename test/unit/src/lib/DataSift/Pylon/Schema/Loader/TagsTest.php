<?php

namespace Test\DataSift\Pylon\Schema\Loader;

use Mockery;

use DataSift\Pylon\Schema\Loader\Tags;

/**
 * TagsLoaderTest
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
 * @covers \DataSift\Pylon\Schema\Loader\Tags
 */
class TagsTest extends \PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @dataProvider provideTagsForLoading
     */
    public function testLoadingTags($hash, $expectedCacheKey, array $tags, array $expectedTags)
    {
        $pylon = Mockery::mock('DataSift_Pylon');
        $pylon->shouldReceive('tags')
            ->with($hash)
            ->andReturn($tags)
            ->once();

        $cache = Mockery::mock('Doctrine\Common\Cache\Cache');
        $cache->shouldReceive('fetch')
            ->with($expectedCacheKey)
            ->andReturn(false)
            ->once();
        $cache->shouldReceive('save')
            ->with($expectedCacheKey, $expectedTags, Mockery::type('integer'))
            ->andReturn(true)
            ->once();

        $recording = Mockery::mock('Tornado\Project\Recording');
        $recording->shouldReceive('getSubscriptionId')
            ->andReturn($hash)
            ->once();

        $tagsLoader = new Tags($pylon, $cache);
        $result = $tagsLoader->load($recording);

        $this->assertEquals($expectedTags, $result);
    }

    /**
     * @dataProvider provideTagsFromCache
     */
    public function testLoadingTagsFromCache($hash, $expectedCacheKey, array $expectedTags)
    {
        $pylon = Mockery::mock('DataSift_Pylon');
        $pylon->shouldNotReceive('tags');

        $cache = Mockery::mock('Doctrine\Common\Cache\Cache');
        $cache->shouldReceive('fetch')
            ->with($expectedCacheKey)
            ->andReturn($expectedTags)
            ->once();
        $cache->shouldNotReceive('save');

        $recording = Mockery::mock('Tornado\Project\Recording');
        $recording->shouldReceive('getSubscriptionId')
            ->andReturn($hash)
            ->once();

        $tagsLoader = new Tags($pylon, $cache);
        $result = $tagsLoader->load($recording);

        $this->assertEquals($expectedTags, $result);
    }

    public function provideTagsForLoading()
    {
        return [
            [ // #0 - no tags
                'hash' => '234dfgd24234',
                'expectedCacheKey' => Tags::CACHE_KEY_PREFIX . '234dfgd24234',
                'tags' => [],
                'expectedTags' => []
            ],
            [ // #1 - some tags
                'hash' => '234dfgd24234',
                'expectedCacheKey' => Tags::CACHE_KEY_PREFIX . '234dfgd24234',
                'tags' => [
                    'interaction.tag_tree.lorem.ipsum',
                    'interaction.tag_tree.lorem.dolor',
                    'interaction.tag_tree.sit_amet',
                    'interaction.tag_tree.adipiscit.elit_quat'
                ],
                'expectedTags' => [
                    [
                        'target' => 'interaction.tag_tree.lorem.ipsum',
                        'label' => 'Lorem Ipsum',
                        'description' => 'VEDO Classification tag.',
                        'is_analysable' => true,
                        'vedo_tag' => true
                    ],
                    [
                        'target' => 'interaction.tag_tree.lorem.dolor',
                        'label' => 'Lorem Dolor',
                        'description' => 'VEDO Classification tag.',
                        'is_analysable' => true,
                        'vedo_tag' => true
                    ],
                    [
                        'target' => 'interaction.tag_tree.sit_amet',
                        'label' => 'Sit Amet',
                        'description' => 'VEDO Classification tag.',
                        'is_analysable' => true,
                        'vedo_tag' => true
                    ],
                    [
                        'target' => 'interaction.tag_tree.adipiscit.elit_quat',
                        'label' => 'Adipiscit Elit Quat',
                        'description' => 'VEDO Classification tag.',
                        'is_analysable' => true,
                        'vedo_tag' => true
                    ],
                ]
            ]
        ];
    }

    public function provideTagsFromCache()
    {
        $data = $this->provideTagsForLoading();
        foreach ($data as &$args) {
            unset($args['tags']);
        }
        return $data;
    }
}
