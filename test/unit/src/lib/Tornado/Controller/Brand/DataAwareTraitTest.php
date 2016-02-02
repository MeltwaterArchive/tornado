<?php

namespace Test\Tornado\Controller;

use Mockery;

use Test\DataSift\ReflectionAccess;

/**
 * DataAwareTraitTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Controller
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers \Tornado\Controller\Brand\DataAwareTrait
 */
class DataAwareTraitTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @covers \Tornado\Controller\Brand\DataAwareTrait::getBrand
     */
    public function testGetBrand()
    {
        $mocks = $this->getMocks();

        $mocks['brandRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brand'])
            ->once();
        $mocks['authorizationManager']->shouldReceive('isGranted')
            ->with($mocks['brand'])
            ->andReturn(true)
            ->once();

        $trait = $this->getTrait($mocks);

        $this->assertSame(
            $mocks['brand'],
            $this->invokeMethod($trait, 'getBrand', [$mocks['brandId']])
        );
    }

    /**
     * @covers \Tornado\Controller\Brand\DataAwareTrait::getBrand
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testGetNotFoundBrand()
    {
        $mocks = $this->getMocks();

        $mocks['brandRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn(null)
            ->once();
        $mocks['authorizationManager']->shouldReceive('isGranted')
            ->never();

        $trait = $this->getTrait($mocks);

        $this->invokeMethod($trait, 'getBrand', [$mocks['brandId']]);
    }

    /**
     * @covers \Tornado\Controller\Brand\DataAwareTrait::getBrand
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testGetDeniedBrand()
    {
        $mocks = $this->getMocks();

        $mocks['brandRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brand'])
            ->once();
        $mocks['authorizationManager']->shouldReceive('isGranted')
            ->with($mocks['brand'])
            ->andReturn(false)
            ->once();

        $trait = $this->getTrait($mocks);

        $this->invokeMethod($trait, 'getBrand', [$mocks['brandId']]);
    }

    /**
     * Prepares test necessarily mocks
     *
     * @return array
     */
    protected function getMocks()
    {
        $brandId = 23;
        $brand = Mockery::mock('Tornado\Organization\Brand', [
            'getId' => $brandId,
            'getPrimaryKey' => $brandId
        ]);

        $brandRepository = Mockery::mock('Tornado\Organization\Brand\DataMapper');
        $authorizationManager = Mockery::mock('Tornado\Security\Authorization\AccessDecisionManagerInterface');

        return [
            'brandId' => $brandId,
            'brand' => $brand,
            'brandRepository' => $brandRepository,
            'authorizationManager' => $authorizationManager
        ];
    }

    /**
     * Setup trait mock
     *
     * @param array $mocks
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getTrait(array $mocks)
    {
        $trait = $this->getMockForTrait('Tornado\Controller\Brand\DataAwareTrait');
        $trait->setBrandRepository($mocks['brandRepository']);
        $trait->setAuthorizationManager($mocks['authorizationManager']);

        return $trait;
    }
}
