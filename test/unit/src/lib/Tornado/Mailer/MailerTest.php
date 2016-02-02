<?php

namespace Test\Tornado\Mailer;

use Mockery;
use \Tornado\Mailer\Mailer;

/**
 * Mailer Test
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Mailer
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \Tornado\Mailer\Mailer
 */
class MailerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::__construct
     * @covers ::send
     */
    public function testSend()
    {
        $return = 'testmessage';
        $self = $this;

        $expected = [
            'from' => 'test@test.com',
            'email' => 'receiver@email.com',
            'subject' => 'Test Subject',
            'body' => '<p>test BODY</p>'
        ];

        $swiftMailer = Mockery::mock('\Swift_Mailer');
        $swiftMailer->shouldReceive('send')
            ->with(Mockery::on(function (\Swift_Message $message) use ($self, $expected) {
                    $self->assertEquals([$expected['from'] => ''], $message->getFrom());
                    $self->assertEquals($expected['subject'], $message->getSubject());
                    $self->assertEquals($expected['body'], $message->getBody());
                    return true;
            }))
            ->andReturn($return);

        $mailer = new Mailer($swiftMailer, $expected['from']);

        $user = Mockery::mock('\Tornado\Organization\User', ['getEmail' => $expected['email']]);

        $this->assertEquals($return, $mailer->send($user, $expected['subject'], $expected['body']));
    }
}
