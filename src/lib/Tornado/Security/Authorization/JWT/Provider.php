<?php

namespace Tornado\Security\Authorization\JWT;

use Tornado\Security\Authorization\JWT\KeyDataMapper;
use Tornado\Security\Authorization\JWT\JWT;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * JWT Provider
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Security\Authorization
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Provider implements \ArrayAccess
{

    /**
     * The mapper from which to obtain JWT keys by UID
     *
     * @var KeyDataMapper
     */
    private $keyMapper;

    /**
     * An associative array of OrganizationId -> JWT Key
     *
     * @var array
     */
    private $keys = [];

    /**
     * Constructs a new JWT key provider
     *
     * @param KeyDataMapper $keyMapper
     * @param JWT $jwt
     */
    public function __construct(KeyDataMapper $keyMapper, JWT $jwt)
    {
        $this->keyMapper = $keyMapper;
        $this->jwt = $jwt;
    }

    /**
     * Decodes and returns a validated JWT token
     *
     * @param string $token
     *
     * @return object
     */
    public function validateToken($token)
    {
        $payload = $this->jwt->decode($token, $this);
        $header = $this->jwt->getHeader($token);

        if (!isset($payload->sub)) {
            throw new Exception('The `sub` element was not provided in the otherwise valid JWT token');
        }

        $payload->iss = $header->kid;

        return $payload;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return ($this->getKey($offset) !== null);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->getKey($offset);
    }

    /**
     * Gets a token from the cache or keyMapper
     *
     * @param mixed $kid
     *
     * @return string|null
     */
    private function getKey($kid)
    {
        if (!array_key_exists($kid, $this->keys)) {
            $this->keys[$kid] = $this->keyMapper->getJwtKey($kid);
        }
        return (isset($this->keys[$kid])) ? $this->keys[$kid] : null;
    }

    /**
     * @inheritDoc
     *
     * @codeCoverageIgnore
     */
    public function offsetSet($offset, $value)
    {
        // NOOP - left in to implement ArrayAccess
    }

    /**
     * @inheritDoc
     *
     * @codeCoverageIgnore
     */
    public function offsetUnset($offset)
    {
        // NOOP - left in to implement ArrayAccess
    }
}
