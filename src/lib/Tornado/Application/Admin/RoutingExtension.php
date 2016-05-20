<?php

namespace Tornado\Application\Admin;

use Tornado\Organization\User;
use Tornado\Organization\Role;
use Symfony\Bridge\Twig\Extension\RoutingExtension as BaseRoutingExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Admin Routing Extension
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Application
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class RoutingExtension extends BaseRoutingExtension
{

    protected $user;

    public function __construct(UrlGeneratorInterface $generator, User $user)
    {
        $this->user = $user;
        parent::__construct($generator);
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        $functions = parent::getFunctions();

        $functions[] = new \Twig_SimpleFunction(
            'orgPath',
            [$this, 'getOrgPath'],
            ['is_safe_callback' => [$this, 'isUrlGenerationSafe']]
        );

        return $functions;
    }

    /**
     * Gets either an admin or superadmin path
     *
     * @param string $name
     * @param mixed $id
     * @param mixed $organizationId
     * @param array $parameters
     * @param boolean $relative
     *
     * @return string
     */
    public function getOrgPath($name, $id, $organizationId = null, array $parameters = [], $relative = false)
    {
        $user = $this->user;
        $path = 'admin.' . (($user->hasRole(Role::ROLE_SUPERADMIN)) ? '' : 'single.') . "organization.$name";
        $parameters['id'] = $id;

        if ($organizationId) {
            $parameters['organizationId'] = $organizationId;
        }

        if (!$user->hasRole(Role::ROLE_SUPERADMIN)) {
            array_pop($parameters);
        }

        return $this->getPath($path, $parameters, $relative);
    }
}
