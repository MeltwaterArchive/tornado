<?php

namespace Controller\Admin;

use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use Tornado\Organization\Role;

use Tornado\Application\Flash\AwareTrait as FlashAwareTrait;

/**
 * OrganizationControllerTrait
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Controller\Admin
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
trait OrganizationControllerTrait
{

    use FlashAwareTrait;
    
    /**
     * @var UrlGenerator
     */
    protected $urlGenerator;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * Gets a list of tabs for display
     *
     * @param mixed $id
     * @param string $selected
     *
     * @return type
     */
    protected function getTabs($id, $selected, $agencyId = false)
    {
        $tabs = [
            'overview' => [
                'label' => 'Overview',
                'url' => $this->getUrl('overview', $id),
                'selected' => ($selected == 'overview')
            ],
            'edit' => [
                'label' => 'Edit',
                'url' => $this->getUrl('edit', $id),
                'selected' => ($selected == 'edit')
            ],
            'users' => [
                'label' => 'Users',
                'url' => $this->getUrl('users', $id),
                'selected' => ($selected == 'users')
            ],
            'agencies' => [
                'label' => 'Agencies',
                'url' => $this->getUrl('agencies', $id),
                'selected' => ($selected == 'agencies')
            ],
        ];

        if ($agencyId) {
            $tabs['brands'] = [
                'label' => 'Brands',
                'url' => $this->getUrl('brands', $agencyId, $id),
                'selected' => ($selected == 'brands')
            ];
        }

        if (!$this->session->get('user')->hasRole(Role::ROLE_SUPERADMIN)) {
            unset($tabs['edit']);
        }

        return $tabs;
    }

    /**
     * Gets a URL depending on the access of the user
     *
     * @param string $path
     * @param mixed $id
     * @param mixed $organizationId
     * @param array $params
     */
    protected function getUrl($path, $id, $organizationId = false, array $params = [])
    {
        $user = $this->getCurrentUser();
        $path = 'admin.'
            . (($user->hasRole(Role::ROLE_SUPERADMIN)) ? '' : 'single.')
            . "organization.$path";

        $params['id'] = $id;

        if ($organizationId) {
            $params['organizationId'] = $organizationId;
        }

        if (!$user->hasRole(Role::ROLE_SUPERADMIN)) {
            array_pop($params);
        }

        return $this->urlGenerator->generate($path, $params);
    }

    /**
     * Gets the current user
     *
     * @return User
     */
    protected function getCurrentUser()
    {
        return $this->session->get('user');
    }
}
