<?php

namespace Test\Tornado\Security\Http;

use \Mockery;

use \DataSift\Http\Request;

use \Tornado\Organization\User;
use \Tornado\Organization\Role;

use \Tornado\Security\Http\AclFirewall;

use \Symfony\Component\HttpFoundation\ParameterBag;

/**
 * AclFirewallTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Security\Http
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \Tornado\Security\Http\AclFirewall
 */
class AclFirewallTest extends \PHPUnit_Framework_TestCase
{
    /**
     * DataProvider for testIsGranted
     *
     * @return array
     */
    public function isGrantedProvider()
    {
        return [
           'No session user' => [
               'request' => $this->getRequest([]),
               'sessionUser' => null,
               'expected' => null,
           ],
           'No required or denied roles' => [
               'request' => $this->getRequest([]),
               'sessionUser' => $this->getUser([]),
               'expected' => true,
           ],
           'Denied roles' => [
                'request' => $this->getRequest([
                     AclFirewall::ACL_DISALLOW_ATTR => ['rolea'],
                     AclFirewall::ACL_PERMISSION_ATTR => ['roleb', 'rolec'],
                 ]),
                'sessionUser' => $this->getUser(['rolea']),
                'expected' => false
            ],
            'No denied roles specified' => [
                'request' => $this->getRequest([
                     AclFirewall::ACL_PERMISSION_ATTR => ['roleb', 'rolec'],
                 ]),
                'sessionUser' => $this->getUser(['roleb']),
                'expected' => true
            ],
            'No permitted roles' => [
                'request' => $this->getRequest([
                     AclFirewall::ACL_DISALLOW_ATTR => [],
                     AclFirewall::ACL_PERMISSION_ATTR => ['roleb', 'rolec'],
                 ]),
                'sessionUser' => $this->getUser(['rolea']),
                'expected' => false
            ]
        ];
    }

    /**
     * @dataProvider isGrantedProvider
     *
     * @covers ::__construct
     * @covers ::isGranted
     *
     * @param \DataSift\Http\Request $request
     * @param \Tornado\Organization\User $sessionUser
     * @param boolean|null $expected
     */
    public function testIsGranted(Request $request, User $sessionUser = null, $expected = null)
    {
        $firewall = new AclFirewall($request, $sessionUser);

        $this->assertEquals($expected, $firewall->isGranted());
    }

    /**
     * Gets a Request for testing
     *
     * @param array $attributes
     *
     * @return \DataSift\Http\Request
     */
    protected function getRequest(array $attributes)
    {
        $request = new Request();
        $request->attributes = new ParameterBag($attributes);
        return $request;
    }

    /**
     * Gets a new User for testing
     *
     * @param array $roles
     *
     * @return \Tornado\Organization\User
     */
    protected function getUser(array $roles)
    {
        $user = new User();
        foreach ($roles as $role) {
            $roleObj = new Role();
            $roleObj->setName($role);
            $user->addRole($roleObj);
        }

        return $user;
    }
}
