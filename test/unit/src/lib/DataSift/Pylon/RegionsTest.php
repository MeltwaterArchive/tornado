<?php

namespace Test\DataSift\Pylon;

use DataSift\Pylon\Regions;

/**
 * RegionsTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\DataSift\Pylon
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers      \DataSift\Pylon\Regions
 */
class RegionsTest extends \PHPUnit_Framework_TestCase
{

    protected function provideRegionsLoader()
    {
        $loader = $this->getMock('\DataSift\Loader\LoaderInterface');
        $loader->expects($this->once())
            ->method('load')
            ->will($this->returnValue([
                'regions.json' => [
                    'countries' => [
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
                    ]
                ]
            ]));
        return $loader;
    }

    public function testGetCountries()
    {
        $regions = new Regions($this->provideRegionsLoader());
        $countries = $regions->getCountries();
        $this->assertEquals(['Denmark', 'Poland', 'United Kingdom'], $countries);
    }

    public function testGetRegions()
    {
        $regions = new Regions($this->provideRegionsLoader());
        $allRegions = $regions->getRegions();
        $this->assertEquals([
            'Arhus',
            'Nordjylland',
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
            'Zachodniopomorskie',
            'England',
            'Northern Ireland',
            'Scotland',
            'Wales'
        ], $allRegions);
    }

    public function testGetCountriesWithRegions()
    {
        $regions = new Regions($this->provideRegionsLoader());
        $everything = $regions->getCountriesWithRegions();
        $this->assertEquals([
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
        ], $everything);
    }
}
