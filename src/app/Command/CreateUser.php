<?php

namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DoctrineRepository;
use Tornado\Organization\Organization;
use Tornado\Organization\User\DataMapper;
use Tornado\Organization\User\Factory as UserFactory;

/**
 * CreateUser Command
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
class CreateUser extends Command
{
    /**
     * Command name
     *
     * @var string
     */
    protected $name = 'tornado:user:create';

    /**
     * @var Organization|null
     */
    protected $organization;

    /**
     * @var DataMapper
     */
    protected $userRepo;

    /**
     * @var DoctrineRepository
     */
    protected $organizationRepo;

    /**
     * @var \Tornado\Organization\User\Factory
     */
    protected $userFactory;

    public function __construct(
        DataMapperInterface $userRepo,
        DataMapperInterface $organizationRepo,
        ValidatorInterface $validator,
        UserFactory $userFactory
    ) {
        parent::__construct($this->name);

        $this->userRepo = $userRepo;
        $this->organizationRepo = $organizationRepo;
        $this->validator = $validator;
        $this->userFactory = $userFactory;
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription('Create a user.')
            ->setDefinition([
                new InputArgument('email', InputArgument::REQUIRED, 'The email'),
                new InputArgument('organization', InputArgument::REQUIRED, 'The Organization name'),
                new InputArgument('password', InputArgument::REQUIRED, 'The password'),
                new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                new InputOption('admin', null, InputOption::VALUE_NONE, 'Set the user as an admin')
            ])
            ->setHelp(<<<EOT
The <info>tornado:user:create</info> command creates a user:
  <info>./src/app/console tornado:user:create</info>
This interactive shell will ask you for an email, organization name to which the user should belongs to, a password
and then an username.
You can alternatively specify the organization, the password and the username as the 2nd, 3rd, 4th arguments:
  <info>./src/app/console tornado:user:create "<email>" "<organizationName> "<password>" "<username>"</info>
You can create an admin via the admin flag:
  <info>./src/app/console tornado:user:create "<email>" --admin</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $organization = $input->getArgument('organization');
        $username = $input->getArgument('username');

        $this->validateOrganizationArgument($organization);
        $this->validateEmailArgument($email);

        $user = $this->userFactory->create([
            'email' => $email,
            'password' => $password,
            'username' => $username,
            'organizationId' => $this->organization->getId()
        ]);

        $this->userRepo->create($user);

        $output->write(sprintf(
            'Created user "%s" (email: "%s") for organization "%s".',
            $username,
            $email,
            $organization
        ), true);
    }

    /**
     * @codeCoverageIgnore
     *
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $arguments = [
            [
                'name' => 'organization',
                'question' => 'Please choose an organization (name) to which User should belong to:',
                'error' => 'Organization can not be empty',
                'validation' => function ($inputValue) {
                    $this->validateOrganizationArgument($inputValue);
                }
            ],
            [
                'name' => 'email',
                'question' => 'Please choose User email:',
                'error' => 'Email can not be empty',
                'validation' => function ($inputValue) {
                    $this->validateEmailArgument($inputValue);
                }
            ],
            [
                'name' => 'password',
                'question' => 'Please choose User password:',
                'error' => 'Password can not be empty'
            ],
            [
                'name' => 'username',
                'question' => 'Please choose User username:',
                'error' => 'Username can not be empty'
            ]
        ];

        foreach ($arguments as $argument) {
            if (!$input->getArgument($argument['name'])) {
                $inputValue = $this->getHelper('dialog')->askAndValidate(
                    $output,
                    $argument['question'],
                    function ($inputValue) use ($argument) {
                        if (empty($inputValue)) {
                            throw new \Exception($argument['error']);
                        }

                        if (isset($argument['validation']) && is_callable($argument['validation'])) {
                            $argument['validation']($inputValue);
                        }

                        return $inputValue;
                    }
                );

                $input->setArgument($argument['name'], $inputValue);
            }
        }
    }

    /**
     * Checks if organization exists in the system
     *
     * @param string $inputValue
     *
     * @throws \InvalidArgumentException if organization not found in the system
     */
    protected function validateOrganizationArgument($inputValue)
    {
        $this->organization = $this->organizationRepo->findOne(['name' => $inputValue]);

        if (!$this->organization) {
            throw new \InvalidArgumentException('Organization not found. Please use existing one.');
        }
    }

    /**
     * Checks if email input argument is valid
     *
     * @param string $inputValue
     *
     * @throws \InvalidArgumentException if email is invalid or already exists in the organization
     */
    protected function validateEmailArgument($inputValue)
    {
        $errors = $this->validator->validate($inputValue, new Assert\Email());

        if (count($errors)) {
            throw new \InvalidArgumentException('Invalid email address.');
        }

        $user = $this->userRepo->findByOrganization($this->organization, ['email' => $inputValue]);

        if ($user) {
            throw new \InvalidArgumentException(sprintf(
                'User with email "%s" already exists for organization "%s".',
                $inputValue,
                $this->organization->getName()
            ));
        }
    }
}
