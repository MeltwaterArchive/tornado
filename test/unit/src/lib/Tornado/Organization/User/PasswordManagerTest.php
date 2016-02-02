<?php

namespace Test\Tornado\Organization\User;

use \Mockery;

use Tornado\Organization\User;
use Tornado\Organization\User\PasswordManager;

use Test\DataSift\ReflectionAccess;

/**
 * PasswordManagerTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Organization\User
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Organization\User\PasswordManager
 */
class PasswordManagerTest extends \PHPUnit_Framework_TestCase
{
    public $code;

    /**
     * @covers ::forgot
     * @covers ::getCacheKey
     */
    public function testForgot()
    {
        $userId = 10;
        $user = Mockery::mock('\Tornado\Organization\User', ['getId' => $userId]);
        $userRepo = Mockery::mock('\Tornado\Organization\User\DataMapper');
        $cache = Mockery::mock('\Doctrine\Common\Cache\Cache');

        $this->code = '';
        $self = $this;

        $cache->shouldReceive('save')
            ->with(
                PasswordManager::FORGOT_PREFIX . $userId,
                Mockery::on(function ($code) use ($self) {
                    $self->code = $code;
                    return ($code !== '');
                }),
                PasswordManager::FORGOT_TTL
            );

        $content = 'Long-winded email';
        $resetTemplate = 'templateA';
        $completeTemplate = 'templateB';
        $twig = Mockery::mock('\Twig_Environment');
        $twig->shouldReceive('render')
            ->with(
                $resetTemplate,
                Mockery::on(function (array $arg) use ($self, $user) {
                    $self->assertTrue(isset($arg['code']));
                    $self->assertTrue(isset($arg['user']));
                    $self->assertEquals($self->code, $arg['code']);
                    $self->assertEquals($user, $arg['user']);
                    return true;
                })
            )->andReturn($content);

        $mailer = Mockery::mock('\Tornado\Mailer\Mailer');
        $mailer->shouldReceive('send')
            ->with($user, 'Password reset', $content);

        $mgr = new PasswordManager(
            $cache,
            $userRepo,
            $mailer,
            $twig,
            $resetTemplate,
            $completeTemplate
        );

        $mgr->forgot($user);
    }

    /**
     * DataProvider for testVerifyForgotCode
     *
     * @return array
     */
    public function verifyForgotCodeProvider()
    {
        return [
            'Happy path' => [
                'randomness',
                'randomness',
                true
            ],
            'Unhappy path' => [
                'randomness',
                'non-randomness',
                false
            ],
            'Unset path' => [
                'randomness',
                false,
                false
            ]
        ];
    }

    /**
     * @dataProvider verifyForgotCodeProvider
     *
     * @covers ::verifyForgotCode
     * @covers ::getCacheKey
     *
     * @param type $code
     * @param type $storedCode
     * @param type $expected
     */
    public function testVerifyForgotCode($code, $storedCode, $expected)
    {
        $userId = 10;
        $user = Mockery::mock('\Tornado\Organization\User', ['getId' => $userId]);
        $userRepo = Mockery::mock('\Tornado\Organization\User\DataMapper');

        $cache = Mockery::mock('\Doctrine\Common\Cache\Cache');
        $cache->shouldReceive('fetch')
            ->with(PasswordManager::FORGOT_PREFIX . $userId)
            ->andReturn($storedCode);

        $resetTemplate = 'templateA';
        $completeTemplate = 'templateB';

        $twig = Mockery::mock('\Twig_Environment');
        $mailer = Mockery::mock('\Tornado\Mailer\Mailer');

        $mgr = new PasswordManager(
            $cache,
            $userRepo,
            $mailer,
            $twig,
            $resetTemplate,
            $completeTemplate
        );

        $this->assertEquals($expected, $mgr->verifyForgotCode($user, $code));
    }

    /**
     * @covers ::resetPassword
     * @covers ::getCacheKey
     */
    public function testResetPassword()
    {
        $userId = 10;
        $password = 'testest';

        $user = Mockery::mock('\Tornado\Organization\User', ['getId' => $userId]);
        $user->shouldReceive('setPassword')
            ->once();
        $userRepo = Mockery::mock('\Tornado\Organization\User\DataMapper');
        $userRepo->shouldReceive('update')
            ->once()
            ->with($user);

        $cache = Mockery::mock('\Doctrine\Common\Cache\Cache');
        $cache->shouldReceive('delete')
            ->with(PasswordManager::FORGOT_PREFIX . $userId);

        $content = 'Long-winded email 2';
        $resetTemplate = 'templateA';
        $completeTemplate = 'templateB';
        $twig = Mockery::mock('\Twig_Environment');
        $twig->shouldReceive('render')
            ->with(
                $completeTemplate,
                ['user' => $user]
            )->andReturn($content);

        $mailer = Mockery::mock('\Tornado\Mailer\Mailer');
        $mailer->shouldReceive('send')
            ->with($user, 'Your Tornado password has been changed', $content);

        $mgr = new PasswordManager(
            $cache,
            $userRepo,
            $mailer,
            $twig,
            $resetTemplate,
            $completeTemplate
        );

        $mgr->resetPassword($user, $password);
    }
}
