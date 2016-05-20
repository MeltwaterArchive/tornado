<?php

namespace Test\Tornado\Organization\Brand\Form;

use Mockery;

use Symfony\Component\Validator\ValidatorBuilder;

use Tornado\Organization\Brand;
use Tornado\Organization\Brand\Form\Update;

/**
 * Brand Update form
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
 * @coversDefaultClass \Tornado\Organization\Brand\Form\Update
 */
class UpdateTest extends \PHPUnit_Framework_TestCase
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
                    'datasiftUsername' => '',
                    'datasiftIdentityId' => 'identityId',
                    'datasiftApikey' => 'apikey'
                ],
                'object' => $this->getBrand([
                    'agency_id' => 10,
                    'name' => 'oldName',
                    'datasift_identity_id' => 'idId',
                    'datasift_apikey' => 'getApiKey'
                ]),
                'agencyId' => 10,
                'brandName' => 'BrandName',
                'identityId' => 'identityId',
                'valid' => true,
                'getters' => [
                    'getAgencyId' => 10,
                    'getName' => 'BrandName',
                    'getDatasiftUsername' => '',
                    'getDatasiftIdentityId' => 'identityId',
                    'getDatasiftApikey' => 'apikey'
                ]
            ],
            'No IdentityId' => [
                'data' => [
                    'agencyId' => 10,
                    'name' => 'BrandName',
                    'datasiftUsername' => '',
                    'datasiftIdentityId' => '',
                    'datasiftApikey' => 'apikey'
                ],
                'object' => $this->getBrand([
                    'agency_id' => 10,
                    'name' => 'oldName',
                    'datasift_identity_id' => 'idId',
                    'datasift_apikey' => 'getApiKey'
                ]),
                'agencyId' => 10,
                'brandName' => 'BrandName',
                'identityId' => '',
                'valid' => false,
                'getters' => [
                    'getAgencyId' => 10,
                    'getName' => 'BrandName',
                    'getDatasiftIdentityId' => '',
                    'getDatasiftUsername' => '',
                    'getDatasiftApikey' => 'apikey'
                ],
                'expectedErrors' => ['datasiftIdentityId']
            ],
            'Agency not found' => [
                'data' => [
                    'agencyId' => 10,
                    'name' => 'BrandName',
                    'datasiftUsername' => '',
                    'datasiftIdentityId' => '',
                    'datasiftApikey' => 'apikey'
                ],
                'object' => $this->getBrand([
                    'agency_id' => 10,
                    'name' => 'oldName',
                    'datasift_identity_id' => 'idId',
                    'datasift_apikey' => 'getApiKey'
                ]),
                'agencyId' => 10,
                'brandName' => 'BrandName',
                'identityId' => '',
                'valid' => false,
                'getters' => [
                    'getAgencyId' => 10,
                    'getName' => 'BrandName',
                    'getDatasiftIdentityId' => '',
                    'getDatasiftUsername' => '',
                    'getDatasiftApikey' => 'apikey'
                ],
                'expectedErrors' => ['agencyId', 'datasiftIdentityId'],
                'agencyFound' => false
            ],
            'Brand found, same id' => [
                'data' => [
                    'agencyId' => 10,
                    'name' => 'BrandName',
                    'datasiftUsername' => '',
                    'datasiftIdentityId' => 'id id',
                    'datasiftApikey' => 'apikey'
                ],
                'object' => $this->getBrand([
                    'id' => 20,
                    'agency_id' => 10,
                    'name' => 'oldName',
                    'datasift_identity_id' => 'idId',
                    'datasift_apikey' => 'getApiKey'
                ]),
                'agencyId' => 10,
                'brandName' => 'BrandName',
                'identityId' => 'id id',
                'valid' => true,
                'getters' => [
                    'getAgencyId' => 10,
                    'getName' => 'BrandName',
                    'getDatasiftUsername' => '',
                    'getDatasiftIdentityId' => 'id id',
                    'getDatasiftApikey' => 'apikey'
                ],
                'expectedErrors' => [],
                'agencyFound' => true,
                'brandFound' => true,
                'identityIdFound' => false,
                'brand' => $this->getBrand(['id' => 20])
            ],
            'Brand found, different id' => [
                'data' => [
                    'agencyId' => 10,
                    'name' => 'BrandName',
                    'datasiftUsername' => '',
                    'datasiftIdentityId' => '',
                    'datasiftApikey' => 'apikey'
                ],
                'object' => $this->getBrand([
                    'id' => 20,
                    'agency_id' => 10,
                    'name' => 'oldName',
                    'datasift_identity_id' => 'idId',
                    'datasift_apikey' => 'getApiKey'
                ]),
                'agencyId' => 10,
                'brandName' => 'BrandName',
                'identityId' => '',
                'valid' => false,
                'getters' => [
                    'getAgencyId' => 10,
                    'getName' => 'BrandName',
                    'getDatasiftUsername' => '',
                    'getDatasiftIdentityId' => '',
                    'getDatasiftApikey' => 'apikey'
                ],
                'expectedErrors' => ['name', 'datasiftIdentityId'],
                'agencyFound' => true,
                'brandFound' => true,
                'identityIdFound' => false,
                'brand' => $this->getBrand(['id' => 30])
            ],
            'Identity ID found, same id' => [
                'data' => [
                    'agencyId' => 10,
                    'name' => 'BrandName',
                    'datasiftUsername' => '',
                    'datasiftIdentityId' => 'abcd',
                    'datasiftApikey' => 'apikey'
                ],
                'object' => $this->getBrand([
                    'id' => 20,
                    'agency_id' => 10,
                    'name' => 'oldName',
                    'datasift_identity_id' => 'idId',
                    'datasift_apikey' => 'getApiKey'
                ]),
                'agencyId' => 10,
                'brandName' => 'BrandName',
                'identityId' => 'abcd',
                'valid' => true,
                'getters' => [
                    'getAgencyId' => 10,
                    'getName' => 'BrandName',
                    'getDatasiftUsername' => '',
                    'getDatasiftIdentityId' => 'abcd',
                    'getDatasiftApikey' => 'apikey'
                ],
                'expectedErrors' => [],
                'agencyFound' => true,
                'brandFound' => false,
                'identityIdFound' => true,
                'brand' => $this->getBrand(['id' => 20])
            ],
            'Identity ID found, different id (allow)' => [
                'data' => [
                    'agencyId' => 10,
                    'name' => 'BrandName',
                    'datasiftUsername' => '',
                    'datasiftIdentityId' => 'abcd',
                    'datasiftApikey' => 'apikey'
                ],
                'object' => $this->getBrand([
                    'id' => 20,
                    'agency_id' => 10,
                    'name' => 'oldName',
                    'datasift_identity_id' => 'idId',
                    'datasift_apikey' => 'getApiKey'
                ]),
                'agencyId' => 10,
                'brandName' => 'BrandName',
                'identityId' => 'abcd',
                'valid' => true,
                'getters' => [
                    'getAgencyId' => 10,
                    'getName' => 'BrandName',
                    'getDatasiftUsername' => '',
                    'getDatasiftIdentityId' => 'abcd',
                    'getDatasiftApikey' => 'apikey'
                ],
                'expectedErrors' => [],
                'agencyFound' => true,
                'brandFound' => false,
                'identityIdFound' => true,
                'brand' => $this->getBrand(['id' => 30])
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
     * @param \Tornado\Organization\Brand $object
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
        Brand $object,
        $agencyId,
        $brandName,
        $identityId,
        $valid,
        array $getters,
        array $expectedErrors = [],
        $agencyFound = true,
        $brandFound = false,
        $identityFound = false,
        $brand = null
    ) {
        $validatorBuilder = new ValidatorBuilder();
        $validator = $validatorBuilder->getValidator();

        $agency = Mockery::mock('\Tornado\Organization\Agency');

        $agencyRepo = Mockery::mock('\Tornado\Organization\Agency\DataMapper');
        $agencyRepo->shouldReceive('findOne')
            ->with(['id' => $agencyId])
            ->andReturn(($agencyFound) ? $agency : false);

        if (!$brand) {
            $brand = Mockery::mock('\Tornado\Organization\Brand', ['getId' => 2023423432]);
        }
        $brandRepo = Mockery::mock('\Tornado\Organization\Brand\DataMapper');
        $brandRepo->shouldReceive('findOne')
            ->with(['agency_id' => $agencyId, 'name' => $brandName])
            ->andReturn(($brandFound) ? $brand : false);

        $brandRepo->shouldReceive('findOne')
            ->with(['datasift_identity_id' => $identityId])
            ->andReturn(($identityFound) ? $brand : false);

        $form = new Update(
            $validator,
            $agencyRepo,
            $brandRepo
        );

        $form->submit($data, $object);
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

        $form = new Update(
            $validator,
            $agencyRepo,
            $brandRepo
        );

        $this->assertTrue(is_array($form->getFields()));
    }

    /**
     * Gets a Brand from the passed array
     *
     * @param array $data
     *
     * @return \Tornado\Organization\Brand
     */
    private function getBrand(array $data)
    {
        $brand = new Brand();
        $brand->loadFromArray($data);
        return $brand;
    }
}
