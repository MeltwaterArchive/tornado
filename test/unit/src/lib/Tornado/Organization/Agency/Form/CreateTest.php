<?php

namespace Test\Tornado\Organization\Agency\Form;

use Mockery;

use Symfony\Component\Validator\ValidatorBuilder;

use Tornado\DataMapper\DataObjectInterface;
use Tornado\Organization\Agency\AgencyDataMapper;
use Tornado\Organization\Agency;
use Tornado\Organization\Agency\Form\Create;

/**
 * Agency Create form
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
 * @coversDefaultClass \Tornado\Organization\Agency\Form\Create
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
                    'organizationId' => 10,
                    'name' => 'AgencyName',
                    'datasiftUsername' => 'agency',
                    'datasiftApikey' => 'apikey'
                ],
                'organizationId' => 10,
                'agencyName' => 'AgencyName',
                'valid' => true,
                'getters' => [
                    'getOrganizationId' => 10,
                    'getName' => 'AgencyName',
                    'getDatasiftUsername' => 'agency',
                    'getDatasiftApikey' => 'apikey'
                ]
            ],
            'No Username' => [
                'data' => [
                    'organizationId' => 10,
                    'name' => 'AgencyName',
                    'datasiftUsername' => '',
                    'datasiftApikey' => 'apikey'
                ],
                'organizationId' => 10,
                'agencyName' => 'AgencyName',
                'valid' => false,
                'getters' => [
                    'getOrganizationId' => 10,
                    'getName' => 'AgencyName',
                    'getDatasiftUsername' => '',
                    'getDatasiftApikey' => 'apikey'
                ],
                'expectedErrors' => ['datasiftUsername']
            ],
            'Organization not found' => [
                'data' => [
                    'organizationId' => 10,
                    'name' => 'AgencyName',
                    'datasiftUsername' => '',
                    'datasiftApikey' => 'apikey'
                ],
                'organizationId' => 10,
                'agencyName' => 'AgencyName',
                'valid' => false,
                'getters' => [
                    'getOrganizationId' => 10,
                    'getName' => 'AgencyName',
                    'getDatasiftUsername' => '',
                    'getDatasiftApikey' => 'apikey'
                ],
                'expectedErrors' => ['organizationId', 'datasiftUsername'],
                'organizationFound' => false
            ],
            'Agency found' => [
                'data' => [
                    'organizationId' => 10,
                    'name' => 'AgencyName',
                    'datasiftUsername' => '',
                    'datasiftApikey' => 'apikey'
                ],
                'organizationId' => 10,
                'agencyName' => 'AgencyName',
                'valid' => false,
                'getters' => [
                    'getOrganizationId' => 10,
                    'getName' => 'AgencyName',
                    'getDatasiftUsername' => '',
                    'getDatasiftApikey' => 'apikey'
                ],
                'expectedErrors' => ['name', 'datasiftUsername'],
                'organizationFound' => true,
                'agencyFound' => true
            ]
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::submit
     * @covers ::getConstraints
     * @covers ::getData
     * @covers ::organizationExists
     * @covers ::agencyExists
     *
     * @dataProvider submitProvider
     *
     * @param array $data
     * @param \Tornado\DataMapper\DataObjectInterface $object
     * @param integer $organizationId
     * @param string $agencyName
     * @param boolean $valid
     * @param array $getters
     * @param array $expectedErrors
     * @param boolean $organizationFound
     * @param boolean $agencyFound
     */
    public function testSubmit(
        array $data,
        $organizationId,
        $agencyName,
        $valid,
        array $getters,
        array $expectedErrors = [],
        $organizationFound = true,
        $agencyFound = false
    ) {
        $validatorBuilder = new ValidatorBuilder();
        $validator = $validatorBuilder->getValidator();

        $organization = Mockery::mock('\Tornado\Organization\Organization');

        $orgRepo = Mockery::mock('\Tornado\Organization\Organization\DataMapper');
        $orgRepo->shouldReceive('findOne')
            ->with(['id' => $organizationId])
            ->andReturn(($organizationFound) ? $organization : false);

        $agency = Mockery::mock('\Tornado\Organization\Agency');
        $agencyRepo = Mockery::mock('\Tornado\Organization\Agency\DataMapper');
        $agencyRepo->shouldReceive('findOne')
            ->with(['organization_id' => $organizationId, 'name' => $agencyName])
            ->andReturn(($agencyFound) ? $agency : false);

        $form = new Create(
            $validator,
            $orgRepo,
            $agencyRepo
        );

        /**
         */
        $form->submit($data);
        $this->assertEquals($valid, $form->isValid());

        $obj = $form->getData();
        $this->assertInstanceOf('\Tornado\Organization\Agency', $obj);
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

        $orgRepo = Mockery::mock('\Tornado\Organization\Organization\DataMapper');
        $agencyRepo = Mockery::mock('\Tornado\Organization\Agency\DataMapper');

        $form = new Create(
            $validator,
            $orgRepo,
            $agencyRepo
        );

        $this->assertTrue(is_array($form->getFields()));
    }
}
