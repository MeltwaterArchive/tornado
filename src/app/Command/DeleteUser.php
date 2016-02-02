<?php

namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\Organization\User\DataMapper;

/**
 * DeleteUser Command
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
 */
class DeleteUser extends Command
{
    /**
     * Command name
     *
     * @var string
     */
    protected $name = 'tornado:user:delete';

    /**
     * @var DataMapper
     */
    protected $userRepo;

    public function __construct(DataMapperInterface $userRepo)
    {
        parent::__construct($this->name);

        $this->userRepo = $userRepo;
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription('Delete a user.')
            ->setDefinition([
                new InputArgument('id', InputArgument::REQUIRED, 'The user ID')
            ])
            ->setHelp(<<<EOT
The <info>tornado:user:delete</info> command deletes a user by its ID:
  <info>./src/app/console tornado:user:delete <id></info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $id = $input->getArgument('id');
        $user = $this->userRepo->findOne(['id' => $id]);

        if (!$user) {
            throw new \InvalidArgumentException(sprintf(
                'User with id="%d" does not exist in the system.',
                $id
            ));
        }

        $this->userRepo->delete($user);
        $output->write(sprintf('Deleted user with id="%d".', $id), true);
    }
}
