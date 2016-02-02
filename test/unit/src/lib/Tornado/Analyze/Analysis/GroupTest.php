<?php

namespace Test\Tornado\Analyze\Analysis;

use Tornado\Analyze\Analysis\Collection;
use Tornado\Analyze\Analysis\Group;

/**
 * GroupTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Analyze
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers      \Tornado\Analyze\Analysis\Group
 */
class GroupTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGetTitle()
    {
        $group = new Group();
        
        $this->assertEmpty($group->getTitle());

        $group->setTitle('Test');
        $this->assertEquals('Test', $group->getTitle());
    }

    /**
     * DataProvider for testSetGetAnalyses()
     *
     * @return array
     */
    public function addGetAnalysesProvider()
    {
        $collectionA = new Collection([]);
        $collectionB = new Collection([]);

        return [
            [ // 0
                'analyses' => [$collectionA],
                'expected' => [$collectionA]
            ],
            [ // 1
                'analyses' => [$collectionA, $collectionB],
                'expected' => [$collectionA, $collectionB]
            ]
        ];
    }

    /**
     * @dataProvider addGetAnalysesProvider
     *
     * @param   array $analyses
     * @param   array $expected
     */
    public function testAddGetAnalyses(array $analyses, array $expected)
    {
        $group = new Group();

        $this->assertEmpty($group->getAnalysisCollections());

        foreach ($analyses as $analysis) {
            $group->addAnalysisCollection($analysis);
        }

        $this->assertEquals($expected, $group->getAnalysisCollections());
    }
}
