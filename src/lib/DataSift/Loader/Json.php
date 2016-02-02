<?php

namespace DataSift\Loader;

use Symfony\Component\Config\FileLocator;

/**
 * Json file data loader
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Loader
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Json extends FileLocator implements LoaderInterface
{
    /**
     * Returns json data from multiple files in the following format:
     * [
     *  path1 => content1,
     *  path2 => content2
     * ]
     *
     * {@inheritdoc}
     */
    public function load()
    {
        $data = [];
        foreach ($this->paths as $path) {
            if (!$this->supports($path)) {
                throw new \InvalidArgumentException(sprintf(
                    '%s supports only json files. "%s" given.',
                    __METHOD__,
                    $path
                ));
            }

            $filePath = $this->loadFile(
                $this->locate($path)
            );

            $parsedData = $this->parseResource($filePath);
            $parseErrorCode = json_last_error();

            if ($parseErrorCode) {
                throw new \RuntimeException(sprintf(
                    "%s JSON parse error: %s (code: %d)",
                    __METHOD__,
                    json_last_error_msg(),
                    $parseErrorCode
                ));
            }

            $data[$path] = $parsedData;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'json' === $type || (is_string($resource) && preg_match('#\.json$#', $resource));
    }

    /**
     * Parses given resource's json content
     *
     * @param string $resource
     *
     * @return array parsed data
     */
    protected function parseResource($resource)
    {
        return json_decode($resource, true);
    }

    /**
     * Loads given JSON file content
     *
     * @param string $resource
     *
     * @return string
     */
    protected function loadFile($resource)
    {
        return file_get_contents($resource);
    }
}
