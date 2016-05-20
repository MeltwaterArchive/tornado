<?php

namespace Tornado\Application\Flash;

use Tornado\Application\Flash\ServiceProvider;

/**
 * A trait to add knowledge of Flash messages to a class
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
trait AwareTrait
{

    /**
     * The provider to use
     *
     * @var Tornado\Application\Flash\ServiceProvider
     */
    private $flashProvider;

    /**
     * Sets a success flash
     *
     * @param string $message
     */
    protected function flashSuccess($message)
    {
        $this->setFlash($message, Message::LEVEL_SUCCESS);
    }

    /**
     * Sets an error flash
     *
     * @param string $message
     */
    protected function flashError($message)
    {
        $this->setFlash($message, Message::LEVEL_ERROR);
    }

    /**
     * Sets a notification flash
     *
     * @param string $message
     */
    protected function flashNotification($message)
    {
        $this->setFlash($message, Message::LEVEL_NOTIFICATION);
    }

    /**
     * Sets a generic flash
     *
     * @param string $message
     * @param string $level
     *
     * @see \Tornado\Application\Flash\ServiceProvider::setFlashToStore
     */
    protected function setFlash($message, $level)
    {
        if ($this->flashProvider) {
            $this->flashProvider->setFlashToStore($message, $level);
        }
    }

    /**
     * Some flash messages are not for the next request, but the current.
     *
     * @param string $message
     * @param string $level
     * @param array $meta
     */
    protected function setRequestFlash($message, $level, array &$meta)
    {
        $meta['__notification'] = [
            'message' => $message,
            'level' => $level
        ];
    }

    /**
     * Sets the Flash provider
     *
     * @param \Tornado\Application\Flash\ServiceProvider $flash
     */
    public function setFlashProvider(ServiceProvider $flash)
    {
        $this->flashProvider = $flash;
    }
}
