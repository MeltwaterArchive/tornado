<?php

namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Connection;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\Project\Recording;

/**
 * Sample Command
 *
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Command
 * @author      Christopher Hoult
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Sample extends Command
{
    /**
     * Command name
     *
     * @var string
     */
    protected $name = 'tornado:recording:sample';

    /**
     * @var DataMapperInterface
     */
    protected $recordingRepository;

    /**
     * @var DataMapperInterface
     */
    protected $agencyRepo;

    /**
     * @var DataMapperInterface
     */
    protected $brandRepo;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    public function __construct(
        DataMapperInterface $recordingRepository,
        DataMapperInterface $agencyRepo,
        DataMapperInterface $brandRepo,
        \Doctrine\DBAL\Connection $connection
    ) {
        parent::__construct($this->name);

        $this->recordingRepository = $recordingRepository;
        $this->agencyRepo = $agencyRepo;
        $this->brandRepo = $brandRepo;
        $this->connection = $connection;
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription('Fetch recording samples')
            ->setDefinition([
                new InputArgument('recording_id', InputArgument::REQUIRED, 'Recording ID'),
            ])
            ->setHelp('');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $recordingId = $input->getArgument('recording_id');
        $recording = $this->recordingRepository->findOne(['id' => $recordingId]);

        if (!$recording) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find a recording with ID %s',
                $recordingId
            ));
        }

        $sampleRepo = new \Tornado\Project\Recording\Sample\DataMapper(
            $this->connection,
            'Tornado\Project\Recording\Sample',
            'recording_sample',
            $this->getDataSiftUser($recording)
        );

        $samples = $sampleRepo->retrieve($recording);
        $output->writeln('Remaining: ' . $samples['remaining']);
    }

    /**
     * Gets the appropriate PYLON client for the passed Recording
     *
     * @param \Tornado\Project\Recording $recording
     *
     * @return \DataSift\Pylon\Pylon
     */
    protected function getDataSiftUser(Recording $recording)
    {
        $brand = $this->brandRepo->findOne(['id' => $recording->getBrandId()]);
        if (!$username = $brand->getDatasiftUsername()) {
            $agency = $this->agencyRepo->findOne(['id' => $brand->getAgencyId()]);
            $username = $agency->getDatasiftUsername();
        }
        $user = new \DataSift_User($username, $brand->getDatasiftApiKey());
        return new \DataSift\Pylon\Pylon($user);
    }
}
