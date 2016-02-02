<?php

namespace Tornado\Application\Flash;

use Tornado\Application\Flash\Message;

use Silex\Application;
use Silex\ServiceProviderInterface;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * A class to manage Flash Messages
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Application\Flash
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * The cookie name to use
     */
    const COOKIE_NAME = 'flash';

    /**
     * The application to provide the service for
     *
     * @var \Silex\Application
     */
    private $app;

    /**
     * The current flash message, as loaded from a cookie
     *
     * @var \Tornado\Application\Flash\Message|null
     */
    private $flash;

    /**
     * If set, this message will be saved in a cookie
     *
     * @var \Tornado\Application\Flash\Message|null
     */
    private $flashToStore;

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Gets the Flash message loaded from the cookie
     *
     * @return \Tornado\Application\Flash\Message|null
     */
    public function getFlash()
    {
        return $this->flash;
    }

    /**
     * Sets the flash message to save
     *
     * @param string $message
     * @param string $level
     */
    public function setFlashToStore($message, $level)
    {
        $this->flashToStore = new Message($message, $level);
    }

    /**
     * Gets the Flash message to save to cookie
     *
     * @return \Tornado\Application\Flash\Message|null
     */
    public function getFlashToStore()
    {
        return $this->flashToStore;
    }

    /**
     * Loads the Flash cookie at the beginning of a request
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $cookie = $event->getRequest()->cookies->get(static::COOKIE_NAME, '');
        if ($cookie && preg_match('/^([^:]+):(.*)$/', $cookie, $matches)) {
            $this->flash = new Message($matches[2], $matches[1]);
        }
    }

    /**
     * If set, saves the Flash to store to a cookie, otherwise clears it
     *
     * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!(HttpKernelInterface::MASTER_REQUEST == $event->getRequestType())) {
            return;
        }

        if ($this->flashToStore) {
            $cookie = $this->flashToStore->getLevel() . ':' . $this->flashToStore->getMessage();
            $event->getResponse()->headers->setCookie(
                new Cookie(
                    static::COOKIE_NAME,
                    $cookie
                )
            );
        } else {
            $event->getResponse()->headers->clearCookie(static::COOKIE_NAME);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        $app['dispatcher']->addListener(KernelEvents::REQUEST, [$this, 'onKernelRequest'], 1);
        $app['dispatcher']->addListener(KernelEvents::RESPONSE, [$this, 'onKernelResponse'], 1);
    }
}
