<?php

namespace Test\DataSift\Http;

use DataSift\Http\Request;

/**
 * RequestTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\DataSift\Http
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers      \DataSift\Http\Request
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers \DataSift\Http\Request::__construct
     * @covers \DataSift\Http\Request::getPostParams
     */
    public function testTranslatingBodyRawDataToRequestParameterBag()
    {
        $rawPostData = [
            'test' => 'value',
            'key' => 'lorem',
            'lipsum' => 'Lorem ipsum dolor sit amet',
            'something' => [
                'whatever' => 'hahaha',
                'adipiscit' => true
            ],
            'items' => ['a', 'b', 'c', 'd']
        ];

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'HTTP_CONTENT_TYPE' => 'application/json'
            ],
            json_encode($rawPostData)
        );

        $this->assertEquals($rawPostData, $request->getPostParams());
    }

    /**
     * @covers \DataSift\Http\Request::__construct
     * @covers \DataSift\Http\Request::getPostParams
     */
    public function testOverridingPOSTDataByBodyParams()
    {
        $rawPostData = [
            'test' => 'value',
            'key' => 'lorem',
            'lipsum' => 'Lorem ipsum dolor sit amet',
            'something' => [
                'whatever' => 'hahaha',
                'adipiscit' => true
            ],
            'items' => ['a', 'b', 'c', 'd']
        ];
        $xWWWFormUrlencodedData = [
            'test' => 'valueFromForm',
            'something' => 'noNested'
        ];

        $request = new Request(
            [],
            $xWWWFormUrlencodedData,
            [],
            [],
            [],
            [
                'HTTP_CONTENT_TYPE' => 'application/json'
            ],
            json_encode($rawPostData)
        );

        $this->assertEquals($rawPostData, $request->getPostParams());
    }

    /**
     * @covers \DataSift\Http\Request::__construct
     * @covers \DataSift\Http\Request::getPostParams
     */
    public function testTranslatingBodyRawDataToRequestParameterBagOnlyForRightContentType()
    {
        $rawPostData = [
            'test' => 'value',
            'key' => 'lorem',
            'lipsum' => 'Lorem ipsum dolor sit amet',
            'something' => [
                'whatever' => 'hahaha',
                'adipiscit' => true
            ],
            'items' => ['a', 'b', 'c', 'd']
        ];
        $xWWWFormUrlencodedData = [
            'test' => 'valueFromForm',
            'something' => 'noNested'
        ];

        $request = new Request(
            [],
            $xWWWFormUrlencodedData,
            [],
            [],
            [],
            [
                'HTTP_CONTENT_TYPE' => 'text/html'
            ],
            json_encode($rawPostData)
        );

        $this->assertEquals(
            [
                'test' => 'valueFromForm',
                'something' => 'noNested'
            ],
            $request->getPostParams()
        );
    }
}
