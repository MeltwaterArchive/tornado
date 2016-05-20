<?php

namespace Tornado\Project\Worksheet;

use MD\Foundation\Utils\ObjectUtils;
use MD\Foundation\Utils\StringUtils;

use Tornado\Analyze\Dimension\Collection as DimensionCollection;
use Tornado\Project\Chart\DataMapper as ChartRepository;
use Tornado\Project\Chart;
use Tornado\Project\Worksheet;
use Tornado\Project\Workbook\DataMapper as WorkbookRepository;
use Tornado\Project\Recording\Sample\DataMapper as RecordingSampleRepository;

use \ZipArchive;

/**
 * Worksheet Exporter exports all charts from a worksheet to a 2 dimensional array
 * which later can be written to a table display (e.g. CSV or Excel sheet).
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Project
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects, PHPMD.ExcessiveClassComplexity)
 */
class Exporter
{

    /**
     * @const The zip file format
     */
    const FORMAT_ZIP = 'zip';

    /**
     * Charts repository.
     *
     * @var ChartRepository
     */
    protected $chartsRepository;

    /**
     * Workbook repository
     *
     * @var \Tornado\Project\Workbook\DataMapper
     */
    protected $workbookRepo;

    /**
     * Recording Sample repository
     *
     * @var \Tornado\Project\Recording\Sample\DataMapper
     */
    protected $recordingSampleRepo;

    /**
     * Constructor.
     *
     * @param \Tornado\Project\Chart\DataMapper              $chartsRepository Charts repository.
     * @param \Tornado\Project\Workbook\DataMapper           $workbookRepo
     * @param \Tornado\Project\Recording\Sample\DataMapper   $recordingSampleRepo
     */
    public function __construct(
        ChartRepository $chartsRepository,
        WorkbookRepository $workbookRepo,
        RecordingSampleRepository $recordingSampleRepo
    ) {
        $this->chartsRepository = $chartsRepository;
        $this->workbookRepo = $workbookRepo;
        $this->recordingSampleRepo = $recordingSampleRepo;
    }

    /**
     * Exports a collection of worksheets in the given format
     *
     * @param type $filename
     * @param array $worksheets
     * @param string $format
     */
    public function exportWorksheets($filename, array $worksheets, $format = self::FORMAT_ZIP)
    {
        switch ($format) {
            case self::FORMAT_ZIP:
            default:
                return $this->exportWorksheetsZip($filename, $worksheets);
        }
    }

    /**
     * Exports all charts from the worksheet to a 2 dimensional array where 1st row are headers.
     *
     * @param  Worksheet $worksheet Worksheet to be exported.
     *
     * @return array
     */
    public function exportWorksheet(Worksheet $worksheet)
    {
        $data = [];
        foreach ($this->exportWorksheetGenerator($worksheet) as $row) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Exports all charts from the worksheet to a 2 dimensional array where 1st row are headers.
     *
     * Returns a Generator that yields single data row.
     *
     * @param  Worksheet $worksheet Worksheet to be exported.
     *
     * @return Generator
     */
    public function exportWorksheetGenerator(Worksheet $worksheet)
    {
        switch ($worksheet->getChartType()) {
            case Chart::TYPE_SAMPLE:
                return $this->exportSampleWorksheetGenerator($worksheet);
            default:
                return $this->exportAnalysisWorksheet($worksheet);
        }
    }

    /**
     * Exports all charts from the worksheet to a 2 dimensional array where 1st row are headers.
     *
     * Returns a Generator that yields single data row.
     *
     * @param  Worksheet $worksheet Worksheet to be exported.
     *
     * @return Generator
     */
    private function exportAnalysisWorksheet(Worksheet $worksheet)
    {
        $dimensionsCollection = $worksheet->getDimensions();
        $targets = $dimensionsCollection
            ? ObjectUtils::pluck($dimensionsCollection->getDimensions(DimensionCollection::ORDER_NATURAL), 'target')
            : [];
        $comparison = $worksheet->getSecondaryRecordingId() || $worksheet->getBaselineDataSetId()
            ? $worksheet->getComparison()
            : false;

        $headers = $this->prepareHeaders($worksheet, $targets);

        yield $headers;

        foreach ($this->chartsRepository->findByWorksheet($worksheet) as $chart) {
            $chartData = $this->exportChartData($chart, $targets, $comparison);

            foreach ($chartData as $row) {
                yield $this->fillExportRow($row, $headers);
            }
        }
    }

    /**
     * Exports all sample data for a sample Worksheet
     *
     * Returns a Generator that yields single data row.
     *
     * @param  Worksheet $worksheet Worksheet to be exported.
     *
     * @return \Generator
     */
    private function exportSampleWorksheetGenerator(Worksheet $worksheet)
    {
        $workbook = $this->workbookRepo->findOne(['id' => $worksheet->getWorkbookId()]);

        $headers = $rows = [];
        $filters = $worksheet->getFilter('generated_csdl');
        $sqlFilter = ['recording_id' => $workbook->getRecordingId()];
        if (!empty($filters)) {
            $sqlFilter['filter_hash'] = md5($filters);
        }
        foreach ($this->recordingSampleRepo->find($sqlFilter) as $sample) {
            $data = $this->flattenData($sample->getData());
            $headers = array_unique(array_merge($headers, array_keys($data)));
            $rows[] = $data;
        }

        $blank = array_fill_keys($headers, '');

        yield array_values($headers);
        foreach ($rows as $row) {
            yield array_values(array_merge($blank, $row));
        }
    }

    /**
     * Flattens the passed object/array into a dot-notation 2D array
     *
     * @param mixed $data
     *
     * @return array
     */
    private function flattenData($data)
    {
        $ret = [];
        foreach ($data as $key => $value) {
            if (is_object($value) || is_array($value)) {
                foreach ($this->flattenData($value) as $k => $v) {
                    $ret[$key . '.' . $k] = $v;
                }
            } else {
                $ret[$key] = $value;
            }
        }

        return $ret;
    }

    /**
     * Prepares the headers row which will also serve as general table structure.
     *
     * @param  Worksheet $worksheet Worksheet to be exported.
     * @param  array     $targets   Worksheet targets in proper order.
     *
     * @return array
     */
    private function prepareHeaders(Worksheet $worksheet, array $targets)
    {
        // first headers are the same as targets
        $headers = $targets;

        $headers[] = 'interactions';
        $headers[] = 'unique_authors';

        if ($worksheet->getSecondaryRecordingId() || $worksheet->getBaselineDataSetId()) {
            $headers[] = 'interactions_'. $worksheet->getComparison();
            $headers[] = 'unique_authors_'. $worksheet->getComparison();
        }

        return $headers;
    }

    /**
     * Exports data from a single chart.
     *
     * @param  Chart            $chart      Chart to be exported.
     * @param  array            $targets    Targets in the parent worksheet.
     * @param  string|boolean   $comparison Active comparison type on the parent worksheet.
     *
     * @return array
     */
    private function exportChartData(Chart $chart, array $targets, $comparison)
    {
        $rows = [];

        switch ($chart->getType()) {
            case Chart::TYPE_TIME_SERIES:
                $rows = $this->exportTimeSeriesChartData($chart, $comparison);
                break;

            case Chart::TYPE_TORNADO:
            case Chart::TYPE_HISTOGRAM:
            default:
                $rows = $this->exportFreqDistChartData($chart, $targets, $comparison);
        }

        return array_values($rows);
    }

    /**
     * Exports data from a frequency distribution chart.
     *
     * @param  Chart            $chart      Chart to be exported.
     * @param  array            $targets    Targets in the parent worksheet.
     * @param  string|boolean   $comparison Active comparison type on the parent worksheet.
     *
     * @return array
     */
    private function exportFreqDistChartData(Chart $chart, array $targets, $comparison)
    {
        $rows = [];
        $chartData = $chart->getData();

        $this->fillChartRows($chartData->interactions, 'interactions', $targets, $comparison, $rows);
        $this->fillChartRows($chartData->unique_authors, 'unique_authors', $targets, $comparison, $rows);

        return $rows;
    }

    /**
     * Exports data from a timeseries chart.
     *
     * @param  Chart            $chart      Chart to be exported.
     * @param  string|boolean   $comparison Active comparison type on the parent worksheet.
     *
     * @return array
     */
    private function exportTimeSeriesChartData(Chart $chart, $comparison)
    {
        $rows = [];
        $chartData = $chart->getData();

        $this->fillTimeSeriesRows($chartData->interactions, 'interactions', $comparison, $rows);
        $this->fillTimeSeriesRows($chartData->unique_authors, 'unique_authors', $comparison, $rows);

        return $rows;
    }

    /**
     * Prepares a row to be exported according to the structure of `$headers`.
     *
     * @param  array  $data    Single row of data.
     * @param  array  $headers Export data headers.
     *
     * @return array
     */
    private function fillExportRow(array $data, array $headers)
    {
        $row = [];

        foreach ($headers as $i => $key) {
            $row[$i] = isset($data[$key]) ? $data[$key] : 0;
        }

        return $row;
    }

    /**
     * Fills rows of single chart data with appropriate measure values.
     *
     * @param  array|stdClass|Iterator $data       Chart data for a single measure.
     * @param  string                  $measure    Measure name (interactions / unique_authors)
     * @param  array                   $targets    List of targets in the relevant worksheet.
     * @param  string|boolean          $comparison Active comparison type or false.
     * @param  array                   &$rows      Rows to be filled with data.
     */
    private function fillChartRows($data, $measure, array $targets, $comparison, array &$rows)
    {
        foreach ($data as $topValue => $items) {
            foreach ($items as $values) {
                // identify the row by row title to cover cases where interactions and unique_authors might not have
                // the same structure / order
                $rowTitle = ' ';
                if (isset($values[3])) {
                    $rowTitle = $this->extractRowTitle($values[3]);
                }
                if (!isset($rows[$rowTitle])) {
                    $rows[$rowTitle] = [];
                }

                // grab reference to row for this data
                $row = &$rows[$rowTitle];
                if (count($targets) > 1) {
                    $row[$targets[0]] = $topValue;
                    $row[$targets[1]] = $values[0];
                    $row[$measure] = $values[1];
                } else {
                    $row[$targets[0]] = $values[0];
                    $row[$measure] = $values[1];
                }

                if ($comparison) {
                    $row[$measure .'_'. $comparison] = $values[2];
                }

                if (isset($targets[2])) {
                    $row[$targets[2]] = $this->extractDimensionValue($values[3], $targets[2]);
                }
            }
        }
    }

    /**
     * Fills rows of time series chart data with appropriate measure values.
     *
     * @param  array|strClass|Iterator  $data       Chart timeseries data for a single measure.
     * @param  string                   $measure    Measure name (interactions / unique_authors)
     * @param  string|boolean          $comparison Active comparison type or false.
     * @param  array                   &$rows      Rows to be filled with data.
     */
    private function fillTimeSeriesRows($data, $measure, $comparison, array &$rows)
    {
        foreach ($data->main as $point) {
            if (!isset($rows[$point[0]])) {
                $rows[$point[0]] = [
                    'time' => date(\DateTime::ATOM, $point[0])
                ];
            }

            $rows[$point[0]][$measure] = $point[1];
        }

        if ($comparison && isset($data->comparison)) {
            foreach ($data->comparison as $point) {
                $rows[$point[0]][$measure .'_'. $comparison] = $point[1];
            }
        }
    }

    /**
     * Extracts row title from chart's explore data, which can be used to identify the row.
     *
     * @param  object $exploreData Chart's explore data.
     *
     * @return string
     */
    private function extractRowTitle($exploreData)
    {
        return array_search(current((array) $exploreData->explore), (array) $exploreData->explore);
    }

    /**
     * Extracts dimension value from chart's explore data.
     *
     * @param  object $exploreData Chart's explore data.
     * @param  string $target      Target name.
     *
     * @return mixed
     */
    private function extractDimensionValue($exploreData, $target)
    {
        $value = null;
        foreach ($exploreData->explore as $data) {
            if (isset($data->{$target})) {
                $value = $data->{$target};
            }
        }
        return $value;
    }

    /**
     * Exports a collection of worksheets in ZIP format
     *
     * @param string $filename
     * @param array $worksheets
     */
    private function exportWorksheetsZip($filename, array $worksheets)
    {
        $archive = new ZipArchive();
        $archive->open($filename, ZipArchive::CREATE);
        foreach ($worksheets as $worksheet) {
            $str = '';
            foreach ($this->exportWorksheetGenerator($worksheet) as $row) {
                $str .= implode(
                    ',',
                    array_map(
                        function ($it) {
                            if (!is_numeric($it)) {
                                return '"' . str_replace('"', '\\"', $it) . '"';
                            }
                            return $it;
                        },
                        $row
                    )
                ) . "\n";
            }
            $archive->addFromString(StringUtils::fileNameFriendly($worksheet->getName()) . '.csv', $str);
        }

        $archive->close();
    }
}
