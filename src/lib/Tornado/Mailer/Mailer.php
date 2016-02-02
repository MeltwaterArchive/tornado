<?php

namespace Tornado\Mailer;

use Tornado\Organization\User;

/**
 * Mailer
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Controller
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Mailer
{

    /**
     * The underlying mailer component
     *
     * @var \Swift_Mailer
     */
    private $swiftMailer;

    /**
     * The sender of this email
     *
     * @var string
     */
    private $from;

    /**
     * Constructs a new Mailer
     *
     * @param \Swift_Mailer $swiftMailer
     * @param string $from
     */
    public function __construct(\Swift_Mailer $swiftMailer, $from)
    {
        $this->swiftMailer = $swiftMailer;
        $this->from = $from;
    }

    /**
     * Sends mail to the passed User
     *
     * @param \Tornado\Organization\User $user
     * @param string $subject
     * @param string $body
     *
     * @return boolean
     */
    public function send(User $user, $subject, $body)
    {
        $message = \Swift_Message::newInstance($subject);
        $message->setFrom([$this->from])
            ->setTo([$user->getEmail()])
            ->setBody(stripslashes(str_replace('<br />', "\n", $body)))
            ->addPart($body, 'text/html');
        
        return $this->swiftMailer->send($message);
    }
}
