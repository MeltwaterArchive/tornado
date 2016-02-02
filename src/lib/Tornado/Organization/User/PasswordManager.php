<?php

namespace Tornado\Organization\User;

use Doctrine\Common\Cache\Cache;
use Tornado\DataMapper\DataMapperInterface;

use \Tornado\Mailer\Mailer;

use \Tornado\Organization\User;

class PasswordManager
{
    const FORGOT_TTL = 1800;
    const FORGOT_PREFIX = 'forgot-password-';

    /**
     * The cache for the reset code
     *
     * @var \Doctrine\Common\Cache\Cache;
     */
    private $cache;

    /**
     * The datamapper for User objects
     *
     * @var \Tornado\DataMapper\DataMapperInterface
     */
    private $userRepo;

    private $mailer;

    /**
     * This manager's Twig
     *
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * The Twig template to use for sending a reset notification
     *
     * @var string
     */
    private $resetTemplate;

    /**
     * The Twig template to use for sending a completion notification
     *
     * @var string
     */
    private $completeTemplate;

    /**
     * Constructs a new PasswordManager
     *
     * @param \Doctrine\Common\Cache\Cache $cache
     * @param Tornado\DataMapper\DataMapperInterface $userRepo
     * @param \Tornado\Mailer\Mailer $mailer
     * @param Twig_Environment $twig
     * @param string $resetTemplate
     * @param string $completeTemplate
     */
    public function __construct(
        Cache $cache,
        DataMapperInterface $userRepo,
        Mailer $mailer,
        \Twig_Environment $twig,
        $resetTemplate,
        $completeTemplate
    ) {
        $this->cache = $cache;
        $this->userRepo = $userRepo;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->resetTemplate = $resetTemplate;
        $this->completeTemplate = $completeTemplate;
    }

    /**
     * Initiates the forgotten password flow
     *
     * @param \Tornado\Organization\User $user
     */
    public function forgot(User $user)
    {
        $code = \password_hash(\uniqid($user->getId() . '-'), PASSWORD_DEFAULT);
        $this->cache->save($this->getCacheKey($user), $code, static::FORGOT_TTL);

        $body = $this->twig->render(
            $this->resetTemplate,
            ['code' => $code, 'user' => $user]
        );

        $this->mailer->send($user, 'Password reset', $body);
    }

    /**
     * Verifies that the passed reset code is valid
     *
     * @param \Tornado\Organization\User $user
     * @param string $code
     *
     * @return boolean
     */
    public function verifyForgotCode(User $user, $code)
    {
        return ($code && $code == $this->cache->fetch($this->getCacheKey($user)));
    }

    /**
     * Sets the password for the passed User, emailing them in the process
     *
     * @param \Tornado\Organization\User $user
     * @param string $password
     */
    public function resetPassword(User $user, $password)
    {
        $user->setPassword(\password_hash($password, PASSWORD_DEFAULT));
        $this->userRepo->update($user);
        $this->cache->delete($this->getCacheKey($user));

        $body = $this->twig->render(
            $this->completeTemplate,
            ['user' => $user]
        );

        $this->mailer->send($user, 'Your Tornado password has been changed', $body);
    }

    /**
     * Gets the key under which to store a user's reset code
     *
     * @param \Tornado\Organization\User $user
     *
     * @return string
     */
    private function getCacheKey(User $user)
    {
        return static::FORGOT_PREFIX . $user->getId();
    }
}
