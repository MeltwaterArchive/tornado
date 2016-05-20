<?php
namespace Test\Tornado\DataMapper;

use Mockery;

use Tornado\Analyze\DataSet\StoredDataSet\DataMapper;

/**
 * @coversDefaultClass \Tornado\Analyze\DataSet\StoredDataSet\DataMapper
 */
class DataMapperTest extends \PHPUnit_Framework_TestCase
{

    /**
     * DataProvider for testFindDataSetsToSchedule
     *
     * @return array
     */
    public function findDataSetsToScheduleProvider()
    {
        return [
            'all good' => [
                'now' => time()
            ]
        ];
    }

    /**
     * @dataProvider findDataSetsToScheduleProvider
     *
     * @covers ::findDataSetsToSchedule
     *
     * @param integer $now
     * @param array $results
     */
    public function testFindDataSetsToSchedule($now)
    {
        $tableName = 'test_table';
        $connection = Mockery::mock('\Doctrine\DBAL\Connection')->shouldAllowMockingProtectedMethods();

        $results = Mockery::mock('\Doctrine\DBAL\Driver\ResultStatement');
        $connection->shouldReceive('query')
            ->andReturnUsing(function ($sql) use ($now, $tableName, $results) {
                if (preg_match("/{$now}/", $sql) && preg_match("/{$tableName}/", $sql)) {
                    return $results;
                }
                return null;
            });

        $dimensionFactory = Mockery::mock('\Tornado\Analyze\Dimension\Factory');

        $output = [];
        $repo = Mockery::mock(
            '\Tornado\Analyze\DataSet\StoredDataSet\DataMapper',
            [],
            [
                $connection,
                '\Tornado\Analyze\DataSet\StoredDataSet',
                $tableName,
                $dimensionFactory
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();
        $repo->shouldReceive('mapResults')
            ->with($results)
            ->andReturn($output);

        $this->assertEquals($output, $repo->findDataSetsToSchedule($now));
    }
}
