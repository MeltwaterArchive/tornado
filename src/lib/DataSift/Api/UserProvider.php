<?php

namespace DataSift\Api;

use DataSift\Pylon\Pylon;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

use DataSift\Api\User as DataSift_User;

/**
 * UserProvider
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Api
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class UserProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected static $SUPPORTED_API_VER = [self::DEFAULT_API_VERSION];
    const DEFAULT_API_VERSION = '1.2';

    /**
     * DataSift API username
     *
     * @var string
     */
    protected $username;

    /**
     * DataSift API api key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * DataSift API URL
     * (Set to false to use the client library default)
     *
     * @var bool|string
     */
    protected $apiUrl = false;

    /**
     * Use SSL for the API?
     *
     * @var bool
     */
    protected $apiSsl = true;

    /**
     * DataSift API version
     *
     * @var string
     */
    protected $apiVersion = self::DEFAULT_API_VERSION;

    public function __construct()
    {
        $this->logger = $this->logger ?: new NullLogger();
    }

    /**
     * Set this User DataSift username
     *
     * @param string $username
     *
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Gets this User DataSift username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Sets this User DataSift api key
     *
     * @param string $apiKey
     *
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Gets this User DataSift api key
     *
     * @return string $apiKey
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param $apiVersion
     *
     * @return $this
     * @throws \DataSift_Exception_InvalidData
     */
    public function setApiVersion($apiVersion)
    {
        if (!in_array($apiVersion, self::$SUPPORTED_API_VER)) {
            throw new \DataSift_Exception_InvalidData(sprintf(
                'Unsupported DataSift API version given: %s.',
                $apiVersion
            ));
        }

        $this->apiVersion = $apiVersion;
        return $this;
    }

    /**
     * Gets this User DataSift api version
     *
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * Set if we should use SSL for the
     * DataSift API
     *
     * @param string $ssl
     *
     * @return $this
     */
    public function setApiSsl($ssl)
    {
        $this->apiSsl = $ssl;

        return $this;
    }

    /**
     * Get if we should use SSL for the
     * DataSift API
     *
     * @return string $apiSsl
     */
    public function getApiSsl()
    {
        return $this->apiSsl;
    }

    /**
     * Sets the DataSift api URL
     *
     * @param string $url
     *
     * @return $this
     */
    public function setApiUrl($url)
    {
        $this->apiUrl = $url;

        return $this;
    }

    /**
     * Gets the DataSift api URL
     *
     * @return string $apiUrl
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * Validates the credentials for this user
     * by calling the getUsage endpoint, if credentials
     * are incorrect an exception will be thrown.
     *
     * @return boolean
     *
     * @throws \DataSift_Exception_AccessDenied
     * @throws \Exception
     */
    public function validateCredentials()
    {
        if (empty($this->username) || empty($this->apiKey)) {
            throw new \DataSift_Exception_AccessDenied('Username and/or api key are missing');
        }
        $user = $this->getInstance();
        $user->getUsage();
        return true;
    }

    /**
     * Validates the identity
     *
     * @param DataSift\Pylon\Pylon $pylonClient
     *
     * @return bool
     *
     * @throws \DataSift_Exception_AccessDenied
     * @throws \DataSift_Exception_CompileFailed
     * @throws \Exception
     */
    public function identityHasPremiumPermissions(Pylon $pylonClient = null)
    {
        if (empty($this->username) || empty($this->apiKey)) {
            throw new \DataSift_Exception_AccessDenied('Username and/or api key are missing');
        }

        try {
            $user = $this->getInstance();
            if (empty($pylonClient)) {
                $pylonClient = new Pylon($user);
            }

            try {
                $pylonClient->validate($user, 'fb.author.highest_education exists');
            } catch (\Exception $ex) {
                throw $ex;
            }
        } catch (\DataSift_Exception_InvalidData $e) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if an Identity with the passed id exists
     *
     * @param string $identityId
     * @param \DataSift\Pylon\Pylon $pylonClient
     *
     * @return boolean
     */
    public function identityExists($identityId, Pylon $pylonClient = null)
    {
        $user = $this->getInstance();
        if (empty($pylonClient)) {
            $pylonClient = new Pylon($user);
        }

        $identity = $pylonClient->getIdentity($identityId);
        return (boolean)(count($identity));
    }


    /**
     * Creates new instance of datasift user with given user credentials
     *
     * @return DataSift_User
     *
     * @throws \DataSift_Exception_InvalidData if username or api aren't supplied
     */
    public function getInstance()
    {

        $dsUser = new DataSift_User(
            $this->getUsername(),
            $this->getApiKey(),
            $this->getApiSsl(),
            false,
            $this->getApiUrl(),
            'v' . $this->getApiVersion()
        );

        $this->logger->info(sprintf(
            '%s instantiated new DataSift_User for user: username="%s", api_key="%s", api_version="%s", api_url="%s".',
            __METHOD__,
            $this->getUsername(),
            $this->getApiKey(),
            $this->getApiVersion(),
            $this->getApiUrl()
        ));

        return $dsUser;
    }
}
