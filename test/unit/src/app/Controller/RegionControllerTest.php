<?php

namespace Test\Controller;

use Controller\RegionController;

/**
 * RegionControllerTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Controller
 * @author      Michał Pałys-Dudek
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \Controller\RegionController
 */
class RegionControllerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::__construct
     * @covers ::regions
     */
    public function testRegions()
    {
        $regionsService = $this->getMockBuilder('\DataSift\Pylon\Regions')
            ->disableOriginalConstructor()
            ->getMock();
        $regionsService->expects($this->once())
            ->method('getRegions')
            ->will($this->returnValue([
                'England',
                'Northern Ireland',
                'Scotland',
                'Wales'
            ]));

        $controller = new RegionController($regionsService);
        $result = $controller->regions();

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $this->assertEquals(200, $result->getHttpCode());

        $data = $result->getData();
        $meta = $result->getMeta();

        $this->assertArrayHasKey('regions', $data);
        $this->assertEquals([
            'England',
            'Northern Ireland',
            'Scotland',
            'Wales'
        ], $data['regions']);

        $this->assertArrayHasKey('count', $meta);
        $this->assertEquals(4, $meta['count']);
    }

    /**
     * @covers ::__construct
     * @covers ::countries
     */
    public function testCountries()
    {
        $regionsService = $this->getMockBuilder('\DataSift\Pylon\Regions')
            ->disableOriginalConstructor()
            ->getMock();
        $regionsService->expects($this->once())
            ->method('getCountries')
            ->will($this->returnValue([
                'Denmark',
                'Poland',
                'United Kingdom'
            ]));

        $request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->query = new \Symfony\Component\HttpFoundation\ParameterBag();

        $controller = new RegionController($regionsService);
        $result = $controller->countries($request);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $this->assertEquals(200, $result->getHttpCode());

        $data = $result->getData();
        $meta = $result->getMeta();

        $this->assertArrayHasKey('countries', $data);
        $this->assertEquals([
            'Denmark',
            'Poland',
            'United Kingdom'
        ], $data['countries']);

        $this->assertArrayHasKey('count', $meta);
        $this->assertEquals(3, $meta['count']);
    }

    /**
     * @covers ::__construct
     * @covers ::countries
     */
    public function testCountriesWithRegions()
    {
        $fullData = [
            'Denmark' => [
                'Arhus',
                'Nordjylland'
            ],
            'Poland' => [
                'Dolnoslaskie',
                'Kujawsko-Pomorskie',
                'Lodzkie',
                'Lubelskie',
                'Lubuskie',
                'Malopolskie',
                'Opolskie',
                'Podkarpackie',
                'Podlaskie',
                'Pomorskie',
                'Slaskie',
                'Swietokrzyskie',
                'Warminsko-Mazurskie',
                'Wielkopolskie',
                'Zachodniopomorskie'
            ],
            'United Kingdom' => [
                'England',
                'Northern Ireland',
                'Scotland',
                'Wales'
            ]
        ];
        $regionsService = $this->getMockBuilder('\DataSift\Pylon\Regions')
            ->disableOriginalConstructor()
            ->getMock();
        $regionsService->expects($this->once())
            ->method('getCountriesWithRegions')
            ->will($this->returnValue($fullData));

        $request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->query = new \Symfony\Component\HttpFoundation\ParameterBag([
            'include' => 'regions'
        ]);

        $controller = new RegionController($regionsService);
        $result = $controller->countries($request);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $this->assertEquals(200, $result->getHttpCode());

        $data = $result->getData();

        $this->assertArrayHasKey('countries', $data);
        $this->assertEquals($fullData, $data['countries']);
    }
}
