<?php
namespace Test\Tornado\Controller;

use Tornado\Controller\Result;

/**
 * @coversDefaultClass \Tornado\Controller\Result
 */
class ResultTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getData
     * @covers ::getHttpCode
     */
    public function testConstructingWithDataAndDefaultHttpCode()
    {
        $data = [
            'lorem' => 'ipsum',
            'dolor' => 'sitamet'
        ];
        $result = new Result($data);

        $this->assertEquals($data, $result->getData());
        $this->assertEquals(200, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::setData
     * @covers ::getData
     * @covers ::setMeta
     * @covers ::getMeta
     * @covers ::getHttpCode
     */
    public function testSettingAndGettingDataAndMeta()
    {
        $initialData = [
            'john' => 'doe'
        ];
        $initialMeta = [
            'count' => 1
        ];
        $result = new Result($initialData, $initialMeta);
        $this->assertEquals($initialData, $result->getData());
        $this->assertEquals($initialMeta, $result->getMeta());

        $data = [
            'lorem' => 'ipsum',
            'dolor' => 'sitamet'
        ];
        $meta = [
            'count' => 2
        ];
        $result->setData($data);
        $result->setMeta($meta);

        $this->assertEquals($data, $result->getData());
        $this->assertEquals($meta, $result->getMeta());
    }

    /**
     * @covers ::__construct
     * @covers ::getHttpCode
     */
    public function testConstructingWithCustomHttpCode()
    {
        $result = new Result([], null, 400);
        $this->assertEquals(400, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::setHttpCode
     * @covers ::getHttpCode
     */
    public function testSettingAndGettingHttpCode()
    {
        $result = new Result([]);
        $this->assertEquals(200, $result->getHttpCode());

        $result->setHttpCode(404);
        $this->assertEquals(404, $result->getHttpCode());
    }
}
