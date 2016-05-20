<?php

namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\Analyze\DataSet\StoredDataSet;
use Tornado\Analyze\DataSet\StoredDataSet\DataMapper as StoredDataSetMapper;
use Tornado\Organization\Brand;
use \Tornado\Analyze\DataSet\Generator as DataSetGenerator;
use DataSift\Stats\Collector as StatsCollector;

/**
 * BrandPermissions Command
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Command
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DatasetProcess extends Command
{
    /**
     * Command name
     *
     * @var string
     */
    protected $name = 'tornado:dataset:process';

    /**
     * @var DataMapperInterface
     */
    protected $agencyRepo;

    /**
     * @var DataMapperInterface
     */
    protected $brandRepo;

    /**
     * @var StoredDataSetMapper
     */
    protected $datasetRepo;

    /**
     * @var DataMapperInterface
     */
    protected $recordingRepo;

    /**
     * The DataSet generator
     *
     * @var \Tornado\Analyze\DataSet\Generator
     */
    protected $datasetGenerator;

    /**
     * @var \DataSift\Stats\Collector
     */
    protected $stats;

    /**
     * An array of Analyzer objects, indexed by brand id
     *
     * @var array
     */
    private $analyzers = [];

    /**
     *
     * @param \Tornado\DataMapper\DataMapperInterface $agencyRepo
     * @param \Tornado\DataMapper\DataMapperInterface $brandRepo
     * @param \Tornado\Analyze\DataSet\StoredDataSet\DataMapper $datasetRepo
     * @param \Tornado\DataMapper\DataMapperInterface $recordingRepo
     * @param \Tornado\Analyze\DataSet\Generator $datasetGenerator
     * @param \DataSift\Stats\Collector $stats
     */
    public function __construct(
        DataMapperInterface $agencyRepo,
        DataMapperInterface $brandRepo,
        StoredDataSetMapper $datasetRepo,
        DataMapperInterface $recordingRepo,
        DataSetGenerator $datasetGenerator,
        StatsCollector $stats
    ) {
        parent::__construct($this->name);

        $this->agencyRepo = $agencyRepo;
        $this->brandRepo = $brandRepo;
        $this->datasetRepo = $datasetRepo;
        $this->recordingRepo = $recordingRepo;
        $this->datasetGenerator = $datasetGenerator;
        $this->stats = $stats;
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription('Manage the DataSift curated datasets')
            ->setDefinition([])
            ->setHelp(<<<EOT
The <info>tornado:dataset:process</info> command refreshes the stored datasets in Tornado
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Fetch each DataSet to update
        $datasets = $this->datasetRepo->findDataSetsToSchedule(time());
        foreach ($datasets as $dataset) {
            $output->writeln("Processing {$dataset->getName()}...");
            $recording = $this->recordingRepo->findOne(['id' => $dataset->getRecordingId()]);
            $analyzer = $this->getAnalyzer($dataset);
            try {
                $analyses = $analyzer->fromStoredDataSet($dataset, $recording);
                $data = $this->datasetGenerator->fromAnalyses($analyses, $dataset->getDimensions());

                $dataset->setData($data->getData());
                $dataset->setLastRefreshed(time());
                $this->datasetRepo->update($dataset);
                $output->writeln("Successfully processed {$dataset->getName()}");
            } catch (\Exception $ex) {
                $output->writeln("<error>There was an error processing {$dataset->getName()}</error>");
                $output->writeln("<error>\t" . get_class($ex) . ": {$ex->getMessage()}</error>");
            }
        }
    }

    /**
     * Gets the appropriate Analyzer for the passed dataset
     *
     * @param \Tornado\Analyze\DataSet\StoredDataSet $dataset
     *
     * @return \Tornado\Analyze\Analyzer
     */
    private function getAnalyzer(StoredDataSet $dataset)
    {
        if (!isset($this->analyzers[$dataset->getBrandId()])) {
            $brand = $this->brandRepo->findOne(['id' => $dataset->getBrandId()]);
            $agency = $this->agencyRepo->findOne(['id' => $brand->getAgencyId()]);
            $user = new \DataSift_User($agency->getDatasiftUsername(), $brand->getDatasiftApiKey());
            $pylon = new \DataSift\Pylon\Pylon($user);
            $this->analyzers[$dataset->getBrandId()] = new \Tornado\Analyze\Analyzer($pylon, $this->stats);
        }

        return $this->analyzers[$dataset->getBrandId()];
    }
}
