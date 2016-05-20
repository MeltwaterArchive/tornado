<?php

namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\Organization\Brand;

/**
 * UserBrands Command
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
class UserBrands extends Command
{
    /**
     * Command name
     *
     * @var string
     */
    protected $name = 'tornado:user:brands';

    /**
     * @var DataMapperInterface
     */
    protected $userRepository;

    /**
     * @var DataMapperInterface
     */
    protected $brandRepository;

    public function __construct(
        DataMapperInterface $userRepository,
        DataMapperInterface $brandRepository
    ) {
        parent::__construct($this->name);

        $this->userRepository = $userRepository;
        $this->brandRepository = $brandRepository;
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription('Manage User\'s brands assignment.')
            ->setDefinition([
                new InputArgument('user_id', InputArgument::REQUIRED, 'User ID'),
                new InputArgument('brands', InputArgument::OPTIONAL, 'List of comma separated brands IDs'),
                new InputOption('clear', null, InputOption::VALUE_NONE, 'Clear all User\'s assignment')
            ])
            ->setHelp(<<<EOT
The <info>tornado:user:brands</info> command manages User's brands assignment.

  <info>./src/app/console tornado:user:brands user_id brands</info>
where "user_id" is numeric ID of the User and "brands" is a comma separated list of
brands IDs to which User will belong to.

  <info>./src/app/console tornado:user:brands user_id</info>
will list the brands to which User belongs to.

  <info>./src/app/console tornado:user:brands user_id --clear</info>
will clear the all User's brands assignment.
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
            $result = $this->userRepository->removeBrands($user);
            $output->writeln(sprintf(
                'Cleared <info>%d</info> Brands to which User "%s" belonged to.',
                $result,
                $user->getEmail()
            ));
            return;
        }

        $brandsString = $input->getArgument('brands');
        // if no brands then just list all to which User belongs to
        if (!$brandsString) {
            $assignedBrands = $this->brandRepository->findUserAssigned($user);
            $names = [];

            foreach ($assignedBrands as $brand) {
                $names[] = sprintf('%s (id:%d)', $brand->getName(), $brand->getId());
            }

            $output->writeln(sprintf(
                "User \"%s\" belongs to the brands:\n<info>%s</info>",
                $user->getEmail(),
                implode(",\n", $names)
            ));
            return;
        }

        $allowedBrands = $this->brandRepository->findUserAllowed($user);
        $allowedBrandsIds = [];

        foreach ($allowedBrands as $brand) {
            $allowedBrandsIds[] = $brand->getId();
        }

        $brandsIds = explode(',', $brandsString);
        foreach ($brandsIds as $brandId) {
            if (!in_array($brandId, $allowedBrandsIds)) {
                throw new \InvalidArgumentException(sprintf(
                    'User "%s" cannot be added to the Brand with ID "%s". Allowed Brands IDs: %s.',
                    $user->getEmail(),
                    $brandId,
                    implode(',', $allowedBrandsIds)
                ));
            }
        }

        $brands = $this->brandRepository->findByIds($brandsIds);
        $this->userRepository->addBrands($user, $brands);

        $output->writeln(sprintf(
            'User "%s" has been successfully added to the Brands %s.',
            $user->getEmail(),
            $brandsString
        ));
    }
}
