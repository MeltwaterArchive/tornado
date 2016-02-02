<?php

namespace Test\Tornado\Organization\Brand\Form;

use Mockery;

use Symfony\Component\Validator\ValidatorBuilder;

use Tornado\Organization\Brand\Form\Create;

/**
 * Brand Create form
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Organization\Brand
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Organization\Brand\Form\Create
 */
class CreateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * DataProvider for testSubmit
     *
     * @return array
     */
    public function submitProvider()
    {
        return [
            'Happy path' => [
                'data' => [
                    'agencyId' => 10,
                    'name' => 'BrandName',
                    'datasiftIdentityId' => 'identityId',
                    'datasiftApikey' => 'apikey'
                ],
                'agencyId' => 10,
                'brandName' => 'BrandName',
                'identityId' => 'identityId',
                'valid' => true,
                'getters' => [
                    'getAgencyId' => 10,
                    'getName' => 'BrandName',
                    'getDatasiftIdentityId' => 'identityId',
                    'getDatasiftApikey' => 'apikey'
                ]
            ],
            'No IdentityId' => [
                'data' => [
                    'agencyId' => 10,
                    'name' => 'BrandName',
                    'datasiftIdentityId' => '',
                    'datasiftApikey' => 'apikey'
                ],
                'agencyId' => 10,
                'brandName' => 'BrandName',
                'identityId' => '',
                'valid' => false,
                'getters' => [
                    'getAgencyId' => 10,
                    'getName' => 'BrandName',
                    'getDatasiftIdentityId' => '',
                    'getDatasiftApikey' => 'apikey'
                ],
                'expectedErrors' => ['datasiftIdentityId']
            ],
            'Agency not found' => [
                'data' => [
                    'agencyId' => 10,
                    'name' => 'BrandName',
                    'datasiftIdentityId' => '',
                    'datasiftApikey' => 'apikey'
                ],
                'agencyId' => 10,
                'brandName' => 'BrandName',
                'identityId' => '',
                'valid' => false,
                'getters' => [
                    'getAgencyId' => 10,
                    'getName' => 'BrandName',
                    'getDatasiftIdentityId' => '',
                    'getDatasiftApikey' => 'apikey'
                ],
                'expectedErrors' => ['agencyId', 'datasiftIdentityId'],
                'agencyFound' => false
            ],
            'Brand found' => [
                'data' => [
                    'agencyId' => 10,
                    'name' => 'BrandName',
                    'datasiftIdentityId' => '',
                    'datasiftApikey' => 'apikey'
                ],
                'agencyId' => 10,
                'brandName' => 'BrandName',
                'identityId' => '',
                'valid' => false,
                'getters' => [
                    'getAgencyId' => 10,
                    'getName' => 'BrandName',
                    'getDatasiftIdentityId' => '',
                    'getDatasiftApikey' => 'apikey'
                ],
                'expectedErrors' => ['name', 'datasiftIdentityId'],
                'agencyFound' => true,
                'brandFound' => true
            ],
            'Identity found' => [
                'data' => [
                    'agencyId' => 10,
                    'name' => 'BrandName',
                    'datasiftIdentityId' => 'abcd',
                    'datasiftApikey' => 'apikey'
                ],
                'agencyId' => 10,
                'brandName' => 'BrandName',
                'identityId' => 'abcd',
                'valid' => false,
                'getters' => [
                    'getAgencyId' => 10,
                    'getName' => 'BrandName',
                    'getDatasiftIdentityId' => 'abcd',
                    'getDatasiftApikey' => 'apikey'
                ],
                'expectedErrors' => ['datasiftIdentityId'],
                'agencyFound' => true,
                'brandFound' => false,
                'identityIdFound' => true
            ]
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::submit
     * @covers ::getConstraints
     * @covers ::getData
     * @covers ::agencyExists
     * @covers ::brandExists
     *
     * @dataProvider submitProvider
     *
     * @param array $data
     * @param \Tornado\DataMapper\DataObjectInterface $object
     * @param integer $agencyId
     * @param string $brandName
     * @param string $identityId
     * @param boolean $valid
     * @param array $getters
     * @param array $expectedErrors
     * @param boolean $agencyFound
     * @param boolean $brandFound
     * @param boolean $identityFound
     */
    public function testSubmit(
        array $data,
        $agencyId,
        $brandName,
        $identityId,
        $valid,
        array $getters,
        array $expectedErrors = [],
        $agencyFound = true,
        $brandFound = false,
        $identityFound = false
    ) {
        $validatorBuilder = new ValidatorBuilder();
        $validator = $validatorBuilder->getValidator();

        $agency = Mockery::mock('\Tornado\Organization\Agency');

        $agencyRepo = Mockery::mock('\Tornado\Organization\Agency\DataMapper');
        $agencyRepo->shouldReceive('findOne')
            ->with(['id' => $agencyId])
            ->andReturn(($agencyFound) ? $agency : false);

        $brand = Mockery::mock('\Tornado\Organization\Brand');
        $brandRepo = Mockery::mock('\Tornado\Organization\Brand\DataMapper');
        $brandRepo->shouldReceive('findOne')
            ->with(['agency_id' => $agencyId, 'name' => $brandName])
            ->andReturn(($brandFound) ? $brand : false);

        $brandRepo->shouldReceive('findOne')
            ->with(['datasift_identity_id' => $identityId])
            ->andReturn(($identityFound) ? $brand : false);

        $form = new Create(
            $validator,
            $agencyRepo,
            $brandRepo
        );

        /**
         */
        $form->submit($data);
        $this->assertEquals($valid, $form->isValid());

        $obj = $form->getData();
        $this->assertInstanceOf('\Tornado\Organization\Brand', $obj);
        foreach ($getters as $getter => $expected) {
            $this->assertEquals($expected, $obj->{$getter}());
        }

        $errors = $form->getErrors();
        $this->assertTrue(is_array($errors));
        $this->assertEquals($expectedErrors, array_keys($errors));
    }

    /**
     * @covers ::getFields
     */
    public function testGetFields()
    {
        $validatorBuilder = new ValidatorBuilder();
        $validator = $validatorBuilder->getValidator();

        $brandRepo = Mockery::mock('\Tornado\Organization\Brand\DataMapper');
        $agencyRepo = Mockery::mock('\Tornado\Organization\Agency\DataMapper');

        $form = new Create(
            $validator,
            $agencyRepo,
            $brandRepo
        );

        $this->assertTrue(is_array($form->getFields()));
    }
}
