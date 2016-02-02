<?php

namespace Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use DataSift\Form\FormInterface;
use DataSift\Http\Request;

use Tornado\Controller\Result;

use Tornado\Organization\User\DataMapper as UserRepository;
use Tornado\Organization\User\PasswordManager;
use Tornado\Organization\Role\DataMapper as RoleRepository;
use Tornado\Security\Authorization\JWT\Provider as JwtProvider;

use Tornado\Application\Flash\AwareTrait as FlashAwareTrait;

use Firebase\JWT\JWT;

/**
 * SecurityController
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
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SecurityController
{
    use FlashAwareTrait;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var \DataSift\Form\FormInterface
     */
    protected $loginForm;

    /**
     * @var UrlGenerator
     */
    protected $urlGenerator;

    /**
     * @var JwtProvider
     */
    protected $jwtProvider;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var RoleRepository
     */
    protected $roleRepository;

    /**
     * @var \DataSift\Form\FormInterface
     */
    protected $forgottenPassForm;

    /**
     * @var \DataSift\Form\FormInterface
     */
    protected $resetPasswordForm;

    /**
     * @var \Tornado\Organization\User\PasswordManager
     */
    protected $passwordManager;

    /**
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \DataSift\Form\FormInterface                               $loginForm
     * @param \Symfony\Component\Routing\Generator\UrlGenerator          $urlGenerator
     * @param \Tornado\Security\Authorization\JWT\Provider               $jwtProvider
     * @param \Tornado\Organization\User\DataMapper                      $userRepository
     * @param \Tornado\Organization\Role\DataMapper                      $roleRepository
     * @param \DataSift\Form\FormInterface                               $forgottenPassForm
     * @param \DataSift\Form\FormInterface                               $resetPasswordForm
     * @param \Tornado\Organization\User\PasswordManager                 $passwordManager
     */
    public function __construct(
        SessionInterface $session,
        FormInterface $loginForm,
        UrlGenerator $urlGenerator,
        JwtProvider $jwtProvider,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        FormInterface $forgottenPassForm,
        FormInterface $resetPasswordForm,
        PasswordManager $passwordManager
    ) {
        $this->session = $session;
        $this->loginForm = $loginForm;
        $this->urlGenerator = $urlGenerator;
        $this->jwtProvider = $jwtProvider;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->forgottenPassForm = $forgottenPassForm;
        $this->resetPasswordForm = $resetPasswordForm;
        $this->passwordManager = $passwordManager;
    }

    /**
     * Processes the POST login request with user credentials or returns the form view with or without login
     * form errors.
     *
     * @param Request $request
     *
     * @return RedirectResponse|Result
     */
    public function login(Request $request)
    {
        if ('POST' === $request->getMethod()) {
            $this->loginForm->submit($request->getPostParams());

            if (!$this->loginForm->isValid()) {
                return new Result(
                    [],
                    $this->loginForm->getErrors('Invalid login details'),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $user = $this->loginForm->getData();
            $roles = $this->roleRepository->findUserAssigned($user);
            $user->setRoles($roles);

            $this->session->start();
            $this->session->set('user', $user);

            return new RedirectResponse(
                $this->urlGenerator->generate('home')
            );
        } elseif ($request->query->get('jwt', false)) {
            return $this->doJwt($request->query->get('jwt'));
        }

        return new Result([]);
    }

    /**
     * The action for when one forgets one's password...
     *
     * @param \DataSift\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Tornado\Controller\Result
     */
    public function forgot(Request $request)
    {
        $form = $this->forgottenPassForm;
        if ($request->getMethod() == Request::METHOD_POST) {
            $postParams = $request->getPostParams();
            $form->submit($postParams);

            if ($form->isValid()) {
                $email = $postParams['email'];

                $user = $this->userRepository->findOne(['email' => $email]);

                if ($user) {
                    $this->passwordManager->forgot($user);
                }

                // We don't want to leak the fact that a user exists...
                $this->flashSuccess('You have been sent a password reset email');
                return new RedirectResponse(
                    $this->urlGenerator->generate('login')
                );
            }
        }
        return new Result([], $form->getErrors('Please enter a valid email address'));
    }

    /**
     * Resets the user's password if they click through from an email
     *
     * @param \DataSift\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Tornado\Controller\Result
     *
     * @throws NotFoundHttpException
     */
    public function reset(Request $request)
    {
        $email = $request->get('email');
        $code = $request->get('code');

        $user = $this->userRepository->findOne(['email' => $email]);
        if (!$user || !$this->passwordManager->verifyForgotCode($user, $code)) {
            throw new NotFoundHttpException('Page not found');
        }

        $form = $this->resetPasswordForm;
        if ($request->getMethod() == Request::METHOD_POST) {
            $postParams = $request->getPostParams();
            $form->submit($postParams, $user);
            if ($form->isValid()) {
                $this->passwordManager->resetPassword($user, $postParams['password']);
                $this->flashSuccess('Your password has been reset. Please login below.');
                return new RedirectResponse(
                    $this->urlGenerator->generate('login')
                );
            }
        }

        return new Result(
            [

            ],
            $form->getErrors('Please ensure both fields are entered and that the passwords match.')
        );
    }

    /**
     * Sign outs a user
     */
    public function logout()
    {
        $this->session->remove('user');

        return new RedirectResponse(
            $this->urlGenerator->generate('login')
        );
    }

    /**
     * Performs the JWT validation/processing
     *
     * @param string $token
     *
     * @return RedirectResponse|Result
     */
    protected function doJwt($token)
    {
        $error = '';
        try {
            $payload = $this->jwtProvider->validateToken($token);

            $user = $this->userRepository->findOne(
                [
                    'organization_id' => $payload->iss,
                    'username' => $payload->sub
                ]
            );

            if (!$user) {
                return new Result([], ['login' => 'Invalid `sub` element in an otherwise valid token']);
            }

            $this->session->start();
            $this->session->set('user', $user);

            /**
             * We don't want to have arbitrary redirection, so just get the path
             */
            $url = (isset($payload->url) && $payload->url)
                    ? parse_url($payload->url, PHP_URL_PATH)
                    : $this->urlGenerator->generate('home');

            return new RedirectResponse(
                $url
            );
        } catch (\RuntimeException $ex) {
            $error = $ex->getMessage();
        }
        return new Result([], ['login' => $error]);
    }
}
