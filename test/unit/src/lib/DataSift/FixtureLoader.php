<?php

namespace Test\DataSift;

/**
 * A fixture-loading trait for use in unit testing
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Test
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
trait FixtureLoader
{
    /**
     * Gets dir absolute path
     *
     * @return string
     */
    protected function getDirPath()
    {
        $ref = new \ReflectionClass(get_class($this));
        return dirname($ref->getFileName());
    }

    /**
     * Loads a fixture from the given file
     *
     * @param string        $name The name of the file to load
     * @param string|null   $path An optional path
     *
     * @return string
     */
    protected function loadFixture($name, $path = null)
    {
        if (!$path) {
            $path = $this->getDirPath();
        }
        $path = rtrim($path, '/') . "/fixtures/{$name}";
        if (!file_exists($path)) {
            throw new \RuntimeException("Could not find fixture file {$path}");
        }

        return file_get_contents($path);
    }

    /**
     * Loads a JSON fixture
     *
     * @param string    $name       The name of the file to load
     * @param string    $path       An optional path
     * @param boolean   $asArray    Whether to load as an array or object
     *
     * @return mixed
     */
    protected function loadJSONFixture($name, $path = null, $asArray = false)
    {
        return json_decode($this->loadFixture("$name.json", $path), $asArray);
    }
}
