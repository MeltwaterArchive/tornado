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
 * BrandPermissions Command
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
class BrandPermissions extends Command
{
    /**
     * Command name
     *
     * @var string
     */
    protected $name = 'tornado:brand:permissions';

    /**
     * @var DataMapperInterface
     */
    protected $brandRepository;

    public function __construct(
        DataMapperInterface $brandRepository
    ) {
        parent::__construct($this->name);

        $this->brandRepository = $brandRepository;
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription('Manage Brand target permissions.')
            ->setDefinition([
                new InputArgument('brand_id', InputArgument::REQUIRED, 'Brand ID'),
                new InputArgument('permissions', InputArgument::OPTIONAL, 'List of comma separated permissions'),
                new InputOption('clear', null, InputOption::VALUE_NONE, 'Clear the permissions')
            ])
            ->setHelp(<<<EOT
The <info>tornado:brand:permissions</info> command manages brand's target permissions.

  <info>./src/app/console tornado:brand:permissions brand_id permissions</info>
where "brand_id" is numeric ID of the brand and "permissions" is a comma separated list of
permissions will set these permissions.

  <info>./src/app/console tornado:brand:permissions brand_id</info>
will list the brand's permissions.

  <info>./src/app/console tornado:brand:permissions brand_id --clear</info>
will clear the brand's permissions.
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $brandId = $input->getArgument('brand_id');
        $brand = $this->brandRepository->findOne(['id' => $brandId]);
        if (!$brand) {
            throw new \InvalidArgumentException(sprintf(
                'Could not find a brand with ID %s',
                $brandId
            ));
        }

        $clear = $input->getOption('clear');
        if ($clear) {
            $brand->setTargetPermissions([]);
            $this->brandRepository->update($brand);
            $output->writeln(sprintf(
                'Cleared target permissions for brand <info>%s</info>',
                $brand->getName()
            ));
            return;
        }

        $permissions = $input->getArgument('permissions');

        // if no permissions then just list them
        if (!$permissions) {
            $output->writeln(sprintf(
                'Target permissions for brand <info>%s</info> are: <comment>%s</comment>',
                $brand->getName(),
                $brand->getRawTargetPermissions()
            ));
            return;
        }

        $brand->setRawTargetPermissions($permissions);
        $this->brandRepository->update($brand);

        $output->writeln(sprintf(
            'Successfully set target permissions for brand <info>%s</info> to: <comment>%s</comment>',
            $brand->getName(),
            $brand->getRawTargetPermissions()
        ));
    }
}
