<?php

namespace Tornado\Security\Authorization\JWT;

use Firebase\JWT\JWT as FirebaseJWT;

/**
 * JWT Wrapper - wraps the JWT class, which is unfortunately static
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
 *
 * @codeCoverageIgnore  Ignoring code coverage for now because it's such a thin wrapper :(
 */
class JWT
{

    const ALGORITHM_HS256 = 'HS256';

    /**
     * An array of allowed algorithms
     *
     * @var array
     */
    private $allowedAlgorithms;

    /**
     * The allowable time drift for this wrapper
     *
     * @var int
     */
    private $drift;

    /**
     * Constructs a new JWT wrapper
     *
     * @param array $allowedAlgorithms
     * @param int $drift
     */
    public function __construct(array $allowedAlgorithms, $drift = 10)
    {
        $this->allowedAlgorithms = $allowedAlgorithms;
        $this->drift = $drift;
    }

    /**
     * @see Firebase\JWT\JWT::decode
     */
    public function decode($jwt, $key)
    {
        $oldLeeway = FirebaseJWT::$leeway;
        FirebaseJWT::$leeway = $this->drift;
        $ret = FirebaseJWT::decode($jwt, $key, $this->allowedAlgorithms);
        FirebaseJWT::$leeway = $oldLeeway;
        return $ret;
    }

    /**
     * @see Firebase\JWT\JWT::encode
     */
    public function encode($payload, $key, $alg = self::ALGORITHM_HS256, $keyId = null, $head = null)
    {
        return FirebaseJWT::encode($payload, $key, $alg, $keyId, $head);
    }

    /**
     * Gets the header from the passed JWT
     * NB - this assumes the token has been validated
     *
     * @param $token
     *
     * @return string
     */
    public function getHeader($token)
    {
        list($header) = explode('.', $token);
        return json_decode(base64_decode($header));
    }
}
