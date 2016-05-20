<?php

namespace Test\Tornado\Organization\Agency\Form;

use Mockery;

use Symfony\Component\Validator\ValidatorBuilder;

use Tornado\DataMapper\DataObjectInterface;
use Tornado\Analyze\DataSet\StoredDataSet;
use Tornado\Analyze\Analysis;
use Tornado\Analyze\DataSet\StoredDataSet\Form;

/**
 * StoredDataSet form tst
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
 * @coversDefaultClass \Tornado\Analyze\DataSet\StoredDataSet\Form
 */
class FormTest extends \PHPUnit_Framework_TestCase
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
                    'name' => 'Test Name',
                    'recordingId' => 10,
                    'filter' => 'test filter',
                    'dimensions' => [
                        'test.target', '', ''
                    ],
                    'visibility' => StoredDataSet::VISIBILITY_PUBLIC,
                    'analysisType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'schedule' => 10,
                    'scheduleUnits' => StoredDataSet::SCHEDULE_UNITS_DAY,
                    'timeRange' => StoredDataSet::TIMERANGE_MONTH
                ],
                'dataSetName' => 'Test Name',
                'valid' => true,
                'getters' => [
                    'getName' => 'Test Name',
                    'getRecordingId' => 10,
                    'getFilter' => 'test filter',
                    'getVisibility' => StoredDataSet::VISIBILITY_PUBLIC,
                    'getAnalysisType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'getSchedule' => 10,
                    'getScheduleUnits' => StoredDataSet::SCHEDULE_UNITS_DAY,
                    'getTimeRange' => StoredDataSet::TIMERANGE_MONTH
                ]
            ],
            'Update happy path' => [
                'data' => [
                    'name' => 'Test Name',
                    'recordingId' => 10,
                    'filter' => 'test filter',
                    'dimensions' => [
                        'test.target', '', ''
                    ],
                    'visibility' => StoredDataSet::VISIBILITY_PUBLIC,
                    'analysisType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'schedule' => 10,
                    'scheduleUnits' => StoredDataSet::SCHEDULE_UNITS_DAY,
                    'timeRange' => StoredDataSet::TIMERANGE_MONTH
                ],
                'dataSetName' => 'Test Name',
                'valid' => true,
                'getters' => [
                    'getName' => 'Test Name',
                    'getRecordingId' => 10,
                    'getFilter' => 'test filter',
                    'getVisibility' => StoredDataSet::VISIBILITY_PUBLIC,
                    'getAnalysisType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'getSchedule' => 10,
                    'getScheduleUnits' => StoredDataSet::SCHEDULE_UNITS_DAY,
                    'getTimeRange' => StoredDataSet::TIMERANGE_MONTH
                ],
                'expectedErrors' => [],
                'dataSetFound' => true
            ]
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::submit
     * @covers ::getConstraints
     * @covers ::getData
     * @covers ::datasetExists
     *
     * @dataProvider submitProvider
     *
     * @param array $data
     * @param string $dataSetName
     * @param boolean $valid
     * @param array $getters
     * @param array $expectedErrors
     * @param boolean $dataSetFound
     * @param boolean $dataSetSame
     */
    public function testSubmit(
        array $data,
        $dataSetName,
        $valid,
        array $getters,
        array $expectedErrors = [],
        $dataSetFound = false,
        $dataSetSame = true
    ) {
        $validatorBuilder = new ValidatorBuilder();
        $validator = $validatorBuilder->getValidator();

        $dataset = new StoredDataSet();
        $dataset->setId(10);

        $existingDataSet = false;
        if ($dataSetFound) {
            $existingDataSet = new StoredDataSet();
            $existingDataSet->setId(($dataSetSame) ? $dataset->getId() : $dataset->getId() + 10);
        }

        $datasetRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface');
        $datasetRepo->shouldReceive('findOne')
            ->with(['name' => $dataSetName])
            ->andReturn($existingDataSet);

        $recordingRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface');

        $schemaProvider = Mockery::mock('\DataSift\Pylon\Schema\Provider');
        $schema = Mockery::mock('\DataSift\Pylon\Schema\Provider');
        $schema->shouldReceive('getTargets')
            ->with(['is_analysable' => true])
            ->andReturn(['test.target']);
        $schemaProvider->shouldReceive('getSchema')
            ->andReturn($schema);
        $dimensionFactory = Mockery::mock('\Tornado\Analyze\Dimension\Factory');
        $dimensionFactory->shouldReceive('getDimensionCollection')
            ->with(
                array_map(
                    function ($item) {
                        return ['target' => $item];
                    },
                    array_filter($data['dimensions'])
                )
            )->andReturn($data['dimensions']);

        $form = new Form(
            $validator,
            $datasetRepo,
            $recordingRepo,
            $schemaProvider,
            $dimensionFactory
        );

        $form->submit($data, $dataset);

        $errors = $form->getErrors();
        $this->assertTrue(is_array($errors));
        $this->assertEquals($expectedErrors, array_keys($errors));

        $this->assertEquals($valid, $form->isValid());

        $obj = $form->getData();
        $this->assertEquals($dataset, $obj);
        foreach ($getters as $getter => $expected) {
            $this->assertEquals($expected, $obj->{$getter}());
        }
    }

    /**
     * @covers ::getFields
     */
    public function testGetFields()
    {
        $validatorBuilder = new ValidatorBuilder();
        $validator = $validatorBuilder->getValidator();

        $datasetRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface');
        $recordingRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface');
        $schemaProvider = Mockery::mock('\DataSift\Pylon\Schema\Provider');
        $schema = Mockery::mock('\DataSift\Pylon\Schema\Provider');
        $schema->shouldReceive('getTargets')
            ->with(['is_analysable' => true])
            ->andReturn(['test.target']);
        $schemaProvider->shouldReceive('getSchema')
            ->andReturn($schema);
        $dimensionFactory = Mockery::mock('\Tornado\Analyze\Dimension\Factory');

        $form = new Form(
            $validator,
            $datasetRepo,
            $recordingRepo,
            $schemaProvider,
            $dimensionFactory
        );

        $this->assertTrue(is_array($form->getFields()));
    }
}
