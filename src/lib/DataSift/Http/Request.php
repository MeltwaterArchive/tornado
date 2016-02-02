<?php

namespace DataSift\Http;

/**
 * Request
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Http
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Request extends \Symfony\Component\HttpFoundation\Request
{
    /**
     * Translates Request body raw data into request ParameterBag.
     * They are added after query and POST data, which means overrides these data.
     *
     * {@inheritdoc}
     *
     * @param array  $query      The GET parameters
     * @param array  $request    The POST parameters
     * @param array  $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array  $cookies    The COOKIE parameters
     * @param array  $files      The FILES parameters
     * @param array  $server     The SERVER parameters
     * @param string $content    The raw body data
     */
    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);

        $rawPost = $this->getContent();

        if (!empty($rawPost)) {
            if (stripos($this->headers->get('Content-Type'), 'application/json') === 0) {
                $post = json_decode($rawPost, true);

                if ($post && is_array($post)) {
                    $this->request->add($post);
                }
            }
        }
    }

    /**
     * Returns request POST params for both application/json and x-www-form-urlencoded content-type.
     *
     * @return array
     */
    public function getPostParams()
    {
        return $this->request->all();
    }
}
