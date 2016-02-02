<?php

namespace Test\Controller;

use Controller\DataSetController;

use Tornado\Analyze\DataSet\StoredDataSet;
use Tornado\Analyze\Dimension;

/**
 * DataSetControllerTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Controller
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \Controller\DataSetController
 */
class DataSetControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::index
     */
    public function testReturnsDataSetList()
    {
        list($dataSetRepo, $dataSets) = $this->getDataSetRepoMock();

        $ctrl = new DataSetController($dataSetRepo);
        $result = $ctrl->index();

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $this->assertInternalType('array', $result->getData());
        $this->assertEquals(200, $result->getHttpCode());

        $this->assertEquals($dataSets, $result->getData());
        $this->assertEquals(['count' => 2], $result->getMeta());
    }

    /**
     * @param bool $empty
     *
     * @return array
     */
    protected function getDataSetRepoMock($empty = false)
    {
        $dataSetRepo = $this->getMockObject('\Tornado\DataMapper\DataMapperInterface', true);
        $dataSets = [
            new StoredDataSet(null, ['a' => 'b']),
            new StoredDataSet(null, ['c' => 'd'])
        ];

        if (!$empty) {
            $dataSetRepo->expects($this->once())
                ->method('find')
                ->willReturn($dataSets);
        }

        return [$dataSetRepo, $dataSets];
    }

    /**
     * @param string $class
     * @param bool   $disableConstructor
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockObject($class, $disableConstructor = false)
    {
        $mockBuilder = $this->getMockBuilder($class);

        if ($disableConstructor) {
            $mockBuilder->disableOriginalConstructor();
        }

        return $mockBuilder->getMock();
    }
}
