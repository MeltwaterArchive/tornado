<?php

namespace Tornado\Security\Http;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;

use DataSift\Http\Request;

use Tornado\Organization\User;

/**
 * AuthenticationFirewall
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Security\Http
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class AuthenticationFirewall
{

    const AUTHENTICATION_ATTR = '_authentication';
    const AUTHENTICATION_ON = 'on';
    const AUTHENTICATION_OFF = 'off';

    /**
     * @var \DataSift\Http\Request
     */
    protected $request;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    protected $session;

    /**
     * @var \Symfony\Component\Routing\Generator\UrlGenerator
     */
    protected $urlGenerator;

    /**
     * @param \DataSift\Http\Request                                     $request
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \Symfony\Component\Routing\Generator\UrlGenerator          $urlGenerator
     */
    public function __construct(Request $request, SessionInterface $session, UrlGenerator $urlGenerator)
    {
        $this->request = $request;
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Controls access to the system secured area which requires an authentication
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
     */
    public function isGranted()
    {
        $loginUrl = $this->urlGenerator->generate('login');
        $user = $this->session->get('user');

        $access = $this->request->attributes->get(self::AUTHENTICATION_ATTR, self::AUTHENTICATION_ON);

        // if session does not exist and url isn't /login redirect to /login page
        if ((!$user || !$user instanceof User)
            && $access == self::AUTHENTICATION_ON
        ) {
            $path = $this->request->getPathInfo();
            $query = $this->request->getQueryString();
            $redirect = urlencode($path . (($query) ? "?$query" : ''));
            return new RedirectResponse($loginUrl . (($redirect) ? "?redirect=$redirect" : ''));
        }

        if (strtoupper($this->request->getMethod()) == 'GET'
            && $this->request->getPathInfo() === $loginUrl
        ) {
            $this->session->remove('user');
        }
    }
}
