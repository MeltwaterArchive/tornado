<?php

namespace Tornado\Application\Flash;

/**
 * Flash messages - designed to disappear after one viewing
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
class Message
{

    const LEVEL_SUCCESS = 'success';
    const LEVEL_ERROR = 'error';
    const LEVEL_NOTIFICATION = 'notification';

    /**
     * The content of this Flash message
     *
     * @var string
     */
    private $message;

    /**
     * The level of this Flash message
     *
     * @var string
     */
    private $level;

    /**
     * Constructs a new Flash message
     *
     * @param string $message
     * @param string $level
     */
    public function __construct($message, $level)
    {
        $this->message = $message;
        $this->level = $level;
    }

    /**
     * Gets the content of this Flash message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Gets the level of this Flash message
     *
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }
}
