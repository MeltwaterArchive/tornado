<?php

namespace Controller\Admin;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

use DataSift\Http\Request;

use Tornado\Controller\Result;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Lists all necessary data for the dashboard homepage. So far, it is user project list & brands.
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
 */
class IndexController
{
    use OrganizationControllerTrait;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var UrlGenerator
     */
    protected $urlGenerator;

    /**
     * @param SessionInterface $session
     * @param UrlGenerator $urlGenerator
     */
    public function __construct(
        SessionInterface $session,
        UrlGenerator $urlGenerator
    ) {
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Displays a page of Admin actions.
     *
     * @return Result
     */
    public function index()
    {
        $user = $this->getCurrentUser();
        if ($user->isSuperAdmin()) {
            $url = $this->urlGenerator->generate('admin.organizations');
        } else {
            $url = $this->urlGenerator->generate('admin.single.organization.overview');
        }
        return new RedirectResponse($url);

        $user = $this->session->get('user');
        $options = [];
        if ($user->hasRole('ROLE_SUPERADMIN')) {
            $options['organizations'] = [
                'url' => $this->urlGenerator->generate('admin.organizations'),
                'label' => 'Organizations'
            ];
        } else {
            $options = [
                'organization' => [
                    'url' => '/admin/organization',
                    'label' => 'Organization'
                ],
                'agencies' => [
                    'url' => '/admin/agencies',
                    'label' => 'Agencies'
                ]
            ];
        }

        $options['users'] = [
            'url' => '/admin/users',
            'label' => 'Users'
        ];
        return new Result(
            ['items' => $options]
        );
    }
}
