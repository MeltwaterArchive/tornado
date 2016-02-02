<?php

namespace Test\Tornado\Application\Admin;

use Tornado\Application\Admin\RoutingExtension;
use Tornado\Organization\Role;
use Mockery;

/**
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Controller
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass Tornado\Application\Admin\RoutingExtension
 */
class RoutingExtensionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::getFunctions
     */
    public function testGetFunctions()
    {
        $extension = new RoutingExtension(
            Mockery::mock('Symfony\Component\Routing\Generator\UrlGeneratorInterface'),
            Mockery::mock('Tornado\Organization\User')
        );
        $functions = $extension->getFunctions();
        $this->assertTrue(is_array($functions));
        $functionNames = [
            'url',
            'path',
            'orgPath'
        ];

        $foundFunctionNames = [];

        foreach ($functions as $function) {
            $this->assertInstanceOf('\Twig_SimpleFunction', $function);
            $foundFunctionNames[] = $function->getName();
        }

        $this->assertEquals($functionNames, $foundFunctionNames);
    }

    /**
     * DataProvider for testGetOrgPath
     *
     * @return array
     */
    public function getOrgPathProvider()
    {
        return [
            'Superadmin, orgid and id' => [
                'isSuperadmin' => true,
                'name' => 'user.edit',
                'id' => 10,
                'organizationId' => 20,
                'parameters' => [
                    'test' => 'value'
                ],
                false,
                'expectedRoute' => 'admin.organization.user.edit',
                'expectedParameters' => [
                    'test' => 'value',
                    'id' => 10,
                    'organizationId' => 20
                ]
            ],
            'Superadmin, id only' => [
                'isSuperadmin' => true,
                'name' => 'user.edit',
                'id' => 10,
                'organizationId' => null,
                'parameters' => [
                    'test' => 'value'
                ],
                false,
                'expectedRoute' => 'admin.organization.user.edit',
                'expectedParameters' => [
                    'test' => 'value',
                    'id' => 10,
                ]
            ],
            'Admin, orgId and id' => [
                'isSuperadmin' => false,
                'name' => 'user.edit',
                'id' => 10,
                'organizationId' => 20,
                'parameters' => [
                    'test' => 'value'
                ],
                false,
                'expectedRoute' => 'admin.single.organization.user.edit',
                'expectedParameters' => [
                    'test' => 'value',
                    'id' => 10,
                ]
            ],
            'Admin, id only' => [
                'isSuperadmin' => false,
                'name' => 'user.edit',
                'id' => 10,
                'organizationId' => null,
                'parameters' => [
                    'test' => 'value'
                ],
                true,
                'expectedRoute' => 'admin.single.organization.user.edit',
                'expectedParameters' => [
                    'test' => 'value'
                ]
            ]
        ];
    }

    /**
     * @dataProvider getOrgPathProvider
     *
     * @covers ::getOrgPath
     * @covers ::__construct
     *
     * @param boolean $isSuperadmin
     * @param string $name
     * @param mixed $id
     * @param mixed $organizationId
     * @param array $parameters
     * @param boolean $relative
     * @param string $expectedRoute
     * @param array $expectedParameters
     */
    public function testGetOrgPath(
        $isSuperadmin,
        $name,
        $id,
        $organizationId,
        array $parameters,
        $relative,
        $expectedRoute,
        array $expectedParameters
    ) {
        $user = Mockery::mock('\Tornado\Organization\User');
        $user->shouldReceive('hasRole')
            ->with(Role::ROLE_SUPERADMIN)
            ->andReturn($isSuperadmin);

        $routingExtension = Mockery::mock(
            '\Tornado\Application\Admin\RoutingExtension[getPath]',
            [
                Mockery::mock('Symfony\Component\Routing\Generator\UrlGeneratorInterface'),
                $user
            ]
        );

        $result = 'testURL';

        $routingExtension->shouldReceive('getPath')
            ->with($expectedRoute, $expectedParameters, $relative)
            ->andReturn($result);

        $this->assertEquals(
            $result,
            $routingExtension->getOrgPath(
                $name,
                $id,
                $organizationId,
                $parameters,
                $relative
            )
        );
    }
}
