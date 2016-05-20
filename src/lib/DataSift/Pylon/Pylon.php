<?php

namespace DataSift\Pylon;

use Tornado\Analyze\Analysis\Collection as AnalysisCollection;
use DataSift\Pylon\Analyze\Request\Collection as RequestCollection;

/**
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Analyze
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Pylon extends \DataSift_Pylon
{

    /**
     * @var \DataSift_User
     */
    protected $user;

    /**
     * Construct the Datasift_Pylon object
     *
     * @param \Datasift_User $user The Datasift user object
     * @param array|boolean $data Data used to populate the attributes of this object
     *
     * @return DataSift_Pylon
     */
    public function __construct($user, $data = false)
    {
        parent::__construct($user, $data);
        $this->user = $user;
    }

    /**
     * Gets the User this client is for
     *
     * @return \DataSift_User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Runs multiple /pylon/analyze calls
     *
     * @param \Tornado\Analyze\Analysis\Collection $analyses
     *
     * @return \Tornado\Analyze\Analysis\Collection
     */
    public function analyzeMulti(AnalysisCollection $analyses)
    {
        $collection = $this->getAnalyzeRequestCollection($analyses);
        $collection->analyze();
        if ($collection->hasErrors()) {
            $errors = $collection->getErrors();
            $error = $errors[0];
            throw new \DataSift_Exception_APIError($error);
        }
        return $analyses;
    }

    /**
     * Gets an AnalyzeRequest Collection for the passed Analysis Collection
     *
     * @param \Tornado\Analyze\Analysis\Collection $analyses
     *
     * @return \DataSift\Pylon\Analyze\Request\Collection
     */
    protected function getAnalyzeRequestCollection(AnalysisCollection $analyses)
    {
        $collection = new RequestCollection($this->user);
        $collection->fromAnalysisCollection($analyses);
        return $collection;
    }

    /**
     * Determines whether a CSDL hash exists...
     *
     * @param string $hash
     *
     * @return boolean
     */
    public function hashExists($hash)
    {
        try {
            $ret = self::validate($this->user, 'stream "' . $hash . '"');
        } catch (\DataSift_Exception_InvalidData $ex) {
            return false;
        }

        return (isset($ret['created_at']));
    }

    /**
     * Gets the identity details for the specified identity id
     *
     * @param $identityId
     * @return mixed
     */
    public function getIdentity($identityId)
    {
        $identity = new \DataSift_Account_Identity($this->user);
        return $identity->get($identityId);
    }
}
