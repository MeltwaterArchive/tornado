<?php

namespace Tornado\Security\Authorization;

/**
 * AccessDecisionManager
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Security\Authorization
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class AccessDecisionManager implements AccessDecisionManagerInterface
{
    /**
     * @var VoterInterface[]
     */
    protected $voters = [];

    /**
     * @param \Tornado\Security\Authorization\VoterInterface $voter
     */
    public function addVoter(VoterInterface $voter)
    {
        $this->voters[] = $voter;
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted($object, $action = null)
    {
        $granted = true;
        foreach ($this->voters as $voter) {
            if (!$voter->supportsClass(get_class($object))) {
                continue;
            }

            if (!$voter->vote($object, $action)) {
                $granted = false;
                break;
            }
        }

        return $granted;
    }
}
