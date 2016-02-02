<?php

namespace DataSift\Pylon;

use DataSift\Loader\LoaderInterface;

/**
 * Pylon regions and countries data
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Pylon
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Regions
{
    /**
     * Map of countries and their regions.
     *
     * @var array
     */
    protected $countries = [];

    /**
     * Data loader.
     *
     * @var LoaderInterface
     */
    protected $loader;
    
    /**
     * Constructor.
     *
     * @param LoaderInterface $loader Data loader.
     */
    public function __construct(LoaderInterface $loader)
    {
        $this->loader = $loader;
        $this->load();
    }

    /**
     * Load regions data into memory.
     *
     * @return array
     */
    protected function load()
    {
        $data = $this->loader->load();
        $this->countries = current($data)['countries'];
        return $this->countries;
    }

    /**
     * Get a list of countries.
     *
     * @return array
     */
    public function getCountries()
    {
        return array_keys($this->countries);
    }

    /**
     * Get a list of all regions from all countries.
     *
     * @return array
     */
    public function getRegions()
    {
        return call_user_func_array('array_merge', $this->countries);
    }

    /**
     * Get a list of countries with their regions.
     *
     * @return array
     */
    public function getCountriesWithRegions()
    {
        return $this->countries;
    }
}
