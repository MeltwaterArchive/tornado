<?php

namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Tornado\DataMapper\DataMapperInterface;

/**
 * UserAgencies Command
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Command
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserAgencies extends Command
{
    /**
     * Command name
     *
     * @var string
     */
    protected $name = 'tornado:user:agencies';

    /**
     * @var DataMapperInterface
     */
    protected $userRepository;

    /**
     * @var DataMapperInterface
     */
    protected $agencyRepository;

    public function __construct(
        DataMapperInterface $userRepository,
        DataMapperInterface $agencyRepository
    ) {
        parent::__construct($this->name);

        $this->userRepository = $userRepository;
        $this->agencyRepository = $agencyRepository;
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription('Manage User\'s agencies assignment.')
            ->setDefinition([
                new InputArgument('user_id', InputArgument::REQUIRED, 'User ID'),
                new InputArgument('agencies', InputArgument::OPTIONAL, 'List of comma separated agencies IDs'),
                new InputOption('clear', null, InputOption::VALUE_NONE, 'Clear all User\'s agencies assignment')
            ])
            ->setHelp(<<<EOT
The <info>tornado:user:agencies</info> command manages User agencies assignment.

  <info>./src/app/console tornado:user:agencies users_id agencies</info>
where "user_id" is numeric ID of the User and "agencies" is a comma separated list of
agencies IDs which will be assigned to the User.

  <info>./src/app/console tornado:user:agencies user_id</info>
will list the existing User agencies.

  <info>./src/app/console tornado:user:agencies user_id --clear</info>
will clear the all User agencies.
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userId = $input->getArgument('user_id');
        $user = $this->userRepository->findOne(['id' => $userId]);
        if (!$user) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find a User with ID %s.',
                $userId
            ));
        }

        $clear = $input->getOption('clear');
        if ($clear) {
            $result = $this->userRepository->removeAgencies($user);
            $output->writeln(sprintf(
                'Cleared <info>%d</info> Agencies to which User "%s" belonged to.',
                $result,
                $user->getEmail()
            ));
            return;
        }

        $agenciesString = $input->getArgument('agencies');
        // if no agencies then just list all to which User belongs to
        if (!$agenciesString) {
            $assignedAgencies = $this->agencyRepository->findUserAssigned($user);
            $names = [];

            foreach ($assignedAgencies as $agency) {
                $names[] = sprintf('%s (id:%d)', $agency->getName(), $agency->getId());
            }

            $output->writeln(sprintf(
                "User \"%s\" belongs to the agencies:\n<info>%s</info>",
                $user->getEmail(),
                implode(",\n", $names)
            ));
            return;
        }

        $allowedAgencies = $this->agencyRepository->findUserAllowed($user);
        $allowedAgenciesIds = [];

        foreach ($allowedAgencies as $agency) {
            $allowedAgenciesIds[] = $agency->getId();
        }

        $agenciesIds = explode(',', $agenciesString);
        foreach ($agenciesIds as $agencyId) {
            if (!in_array($agencyId, $allowedAgenciesIds)) {
                throw new \InvalidArgumentException(sprintf(
                    'User "%s" cannot be added to the Agency with ID "%s". Allowed Agencies IDs: %s.',
                    $user->getEmail(),
                    $agencyId,
                    implode(',', $allowedAgenciesIds)
                ));
            }
        }

        $agencies = $this->agencyRepository->findByIds($agenciesIds);
        $this->userRepository->addAgencies($user, $agencies);

        $output->writeln(sprintf(
            'User "%s" has been successfully added to the Agencies %s.',
            $user->getEmail(),
            $agenciesString
        ));
    }
}
