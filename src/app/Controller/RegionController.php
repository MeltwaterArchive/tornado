<?php

namespace Controller;

use Symfony\Component\HttpFoundation\Request;

use DataSift\Pylon\Regions;

use Tornado\Controller\Result;

/**
 * Returns list of available regions and countries
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Controller
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class RegionController
{
    /**
     * @var Regions
     */
    protected $regions;

    /**
     * @param Regions $regions
     */
    public function __construct(Regions $regions)
    {
        $this->regions = $regions;
    }

    /**
     * Returns list of all available regions.
     *
     * @return \Tornado\Controller\Result
     */
    public function regions()
    {
        $regions = $this->regions->getRegions();
        return new Result([
            'regions' => $regions
        ], [
            'count' => count($regions)
        ]);
    }

    /**
     * Returns list of all available countries.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return \Tornado\Controller\Result
     */
    public function countries(Request $request)
    {
        if ($request->query->get('include', null) === 'regions') {
            $countries = $this->regions->getCountriesWithRegions();
        } else {
            $countries = $this->regions->getCountries();
        }

        return new Result([
            'countries' => $countries
        ], [
            'count' => count($countries)
        ]);
    }
}
