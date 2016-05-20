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
use Tornado\Application\Flash\Message as Flash;

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
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects,PHPMD.ExcessiveParameterList)
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
     * The session handler
     *
     * @var \SessionHandlerInterface
     */
    protected $sessionHandler;

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
     * @param \SessionHandlerInterface                                   $sessionHandler
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
        PasswordManager $passwordManager,
        \SessionHandlerInterface $sessionHandler
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
        $this->sessionHandler = $sessionHandler;
    }

    /**
     * Processes the POST login request with user credentials or returns the form view with or without login
     * form errors.
     *
     * @param Request $request
     * @param string $jwt
     *
     * @return RedirectResponse|Result
     */
    public function login(Request $request, $jwt = false)
    {
        if ('POST' === $request->getMethod()) {
            return $this->doLogin($request);
        } elseif ($request->query->get('jwt', false)) {
            return $this->doJwt($request->query->get('jwt'));
        } elseif ($jwt !== false) {
            return $this->doJwt($jwt);
        }

        return new Result(['redirect' => urldecode($request->query->get('redirect'))]);
    }

    /**
     * The login flow for Tornado
     *
     * @param \DataSift\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Tornado\Controller\Result
     */
    private function doLogin(Request $request)
    {
        $postParams = $request->getPostParams();
        $redirect = '';
        if (isset($postParams['redirect'])) {
            $parts = parse_url($postParams['redirect']);
            $redirect = $parts['path'] . ((isset($parts['query']) && $parts['query']) ? "?{$parts['query']}" : '');
        }

        $this->loginForm->submit($postParams);

        if (!$this->loginForm->isValid()) {
            return new Result(
                ['redirect' => $redirect],
                $this->loginForm->getErrors('Invalid login details'),
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->loginForm->getData();

        if ($user->isDisabled()) {
            $meta = [];
            $this->setRequestFlash('Account disabled', Flash::LEVEL_ERROR, $meta);
            return new Result(
                [],
                $meta,
                Response::HTTP_BAD_REQUEST
            );
        }

        $roles = $this->roleRepository->findUserAssigned($user);
        $user->setRoles($roles);

        $this->session->start();
        $this->session->set('user', $user);

        $id = $this->session->getId();

        /**
         * Store the session id of the user in the session storage
         */
        $this->sessionHandler->write("session-{$user->getId()}", $id);

        $redirect = ($redirect) ? $redirect : $this->urlGenerator->generate('home');
        return new RedirectResponse($redirect);
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
