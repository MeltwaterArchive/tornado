<?php

namespace Tornado\Project\Recording;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

use Tornado\Security\Authorization\VoterInterface;
use Tornado\Organization\Brand;
use Tornado\Organization\Brand\DataMapper as BrandRepository;
use Tornado\Organization\User;

/**
 * UserAccessVoter
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Project\Recording
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class UserAccessVoter implements VoterInterface
{
    /**
     * @var User|null
     */
    protected $sessionUser;

    /**
     * @var \Tornado\Organization\Brand\DataMapper
     */
    protected $brandRepo;

    public function __construct(SessionInterface $session, BrandRepository $brandRepo)
    {
        $this->sessionUser = $session->get('user');
        $this->brandRepo = $brandRepo;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return 'Tornado\Project\Recording' === $class;
    }

    /**
     * @param \Tornado\Project\Recording $object
     *
     * {@inheritdoc}
     */
    public function vote($object, $action = null)
    {
        if (!$this->sessionUser) {
            return false;
        }

        $brand = $this->brandRepo->findOne(['id' => $object->getBrandId()]);
        return $this->brandRepo->isUserAllowed($this->sessionUser, $brand);
    }
}
