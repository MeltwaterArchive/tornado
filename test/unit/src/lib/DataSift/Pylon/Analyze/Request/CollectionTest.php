<?php

namespace Test\DataSift\Pylon\Analyze\Request;

use DataSift\Pylon\Analyze\Request\Collection as RequestCollection;
use Tornado\Analyze\Analysis\Collection as AnalysisCollection;

use Mockery;

/**
 * CollectionTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\DataSift\Pylon
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass  DataSift\Pylon\Analyze\Request\Collection
 */
class Collection extends \PHPUnit_Framework_TestCase
{

    /**
     * DataProvider for testFromAnalysisCollection
     *
     * @return array
     */
    public function fromAnalysisCollectionProvider()
    {
        $user = Mockery::mock('DataSift_User');
        return [
            'No collection' => [
                'user' => $user,
                'analyses' => null,
                'expectedRequests' => 0
            ],
            'Collection' => [
                'user' => $user,
                'analyses' => new AnalysisCollection([
                    Mockery::mock('\Tornado\Analyze\Analysis'),
                    Mockery::mock('\Tornado\Analyze\Analysis'),
                    Mockery::mock('\Tornado\Analyze\Analysis'),
                    Mockery::mock('\Tornado\Analyze\Analysis'),
                    Mockery::mock('\Tornado\Analyze\Analysis')
                ]),
                'expectedRequests' => 5
            ]
        ];
    }

    /**
     * @dataProvider fromAnalysisCollectionProvider
     *
     * @covers ::fromAnalysisCollection
     * @covers ::__construct
     * @covers ::getRequests
     *
     * @param \Test\DataSift\Pylon\Analyze\Request\DataSift_User $user
     * @param \Test\DataSift\Pylon\Analyze\Request\AnalysisCollection $analyses
     */
    public function testFromAnalysisCollection(
        \DataSift_User $user,
        AnalysisCollection $analyses = null,
        $expectedRequests = 0
    ) {
        $collection = new RequestCollection($user, $analyses);
        $requests = $collection->getRequests();
        $this->assertTrue(is_array($requests));
        $this->assertEquals($expectedRequests, count($requests));
        foreach ($requests as $request) {
            $this->assertEquals($user, $request->getUser());
        }

        if ($analyses) {
            $totalFound = 0;
            foreach ($analyses->getAnalyses() as $analysis) {
                $found = false;
                foreach ($requests as $request) {
                    if ($request->getAnalysis() == $analysis) {
                        $found = true;
                    }
                }
                $this->assertTrue($found);
                $totalFound++;
            }
            $this->assertEquals($expectedRequests, $totalFound);
        }
    }

    /**
     * @covers ::addRequest
     */
    public function testAddRequest()
    {
        $user = Mockery::Mock('DataSift_User');
        $collection = new RequestCollection($user);
        $this->assertEquals(0, count($collection->getRequests()));

        $req1 = Mockery::mock('\DataSift\Pylon\Analyze\Request');
        $collection->addRequest($req1);

        $this->assertEquals(1, count($collection->getRequests()));
        $this->assertEquals([$req1], $collection->getRequests());
    }

    /**
     * DataProvider for testGetHasErrors
     *
     * @return array
     */
    public function getHasErrorsProvider()
    {
        return [
            'No errors' => [
                'errorList' => ['', '', ''],
                'expectedHasErrors' => false,
                'expectedErrors' => []
            ],
            'All errors' => [
                'errorList' => ['error1', 'error2', 'error3'],
                'expectedHasErrors' => true,
                'expectedErrors' => ['error1', 'error2', 'error3']
            ],
            'Some errors' => [
                'errorList' => ['error1', '', 'error3'],
                'expectedHasErrors' => true,
                'expectedErrors' => ['error1', 'error3']
            ]
        ];
    }

    /**
     * @dataProvider getHasErrorsProvider
     *
     * @covers ::hasErrors
     * @covers ::getErrors
     *
     * @param array $errorList
     * @param boolean $expectedHasErrors
     * @param array $expectedErrors
     */
    public function testGetHasErrors(array $errorList, $expectedHasErrors, array $expectedErrors)
    {
        $user = Mockery::Mock('DataSift_User');
        $collection = new RequestCollection($user);
        $this->assertEquals(0, count($collection->getRequests()));

        foreach ($errorList as $error) {
            $req = Mockery::mock('\DataSift\Pylon\Analyze\Request');
            $req->shouldReceive('hasError')
                ->andReturn($error ? true : false);
            $req->shouldReceive('getError')
                ->andReturn($error);
            $collection->addRequest($req);
        }

        $this->assertEquals($expectedHasErrors, $collection->hasErrors());
        $this->assertEquals($expectedErrors, $collection->getErrors());
    }
}
