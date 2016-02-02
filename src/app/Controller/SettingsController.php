<?php

namespace Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use DataSift\Form\FormInterface;
use DataSift\Http\Request;

use Tornado\Controller\Result;
use Tornado\Application\Flash\Message as Flash;
use Tornado\Application\Flash\AwareTrait as FlashAwareTrait;
use Tornado\Organization\User\DataMapper as UserDataMapper;

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
  */
class SettingsController
{
    use FlashAwareTrait;

    /**
     * The session object for this controller
     *
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private $session;

    private $emailForm;

    private $passwordForm;

    private $userRepo;

    private $urlGenerator;

    /**
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \DataSift\Form\FormInterface $emailForm
     * @param \DataSift\Form\FormInterface $passwordForm
     * @param \Symfony\Component\Routing\Generator\UrlGenerator $urlGenerator
     */
    public function __construct(
        SessionInterface $session,
        FormInterface $emailForm,
        FormInterface $passwordForm,
        UserDataMapper $userRepo,
        UrlGenerator $urlGenerator
    ) {
        $this->session = $session;
        $this->emailForm = $emailForm;
        $this->passwordForm = $passwordForm;
        $this->userRepo = $userRepo;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * An index page for a user's settings
     *
     * @return \Tornado\Controller\Result
     */
    public function settings(Request $request)
    {
        $meta = [];
        $action = '';

        $user = $this->userRepo->findOne(['id' => $this->getCurrentUser()->getId()]);

        if ($request->getMethod() == Request::METHOD_POST) {
            $params = $request->getPostParams();
            $action = (isset($params['action'])) ? $params['action'] : '';
            unset($params['action']);
            $success = '';
            switch ($action) {
                case 'changepassword':
                    $form = $this->passwordForm;
                    $success = 'Your password has been changed successfully.';
                    break;
                case 'changeemail':
                    $form = $this->emailForm;
                    $success = 'Your email address has been changed successfully.';
                    break;
                default:
                    throw new BadRequestHttpException('Invalid action');
            }

            $form->submit($params, $user);
            if ($form->isValid()) {
                $user = $form->getData();
                $this->userRepo->update($user);

                $this->flashSuccess($success);

                return new RedirectResponse(
                    $this->urlGenerator->generate('settings')
                );
            }
            $meta = $form->getErrors();
            $meta['__notification'] = new Flash('There were errors in your form, please try again', Flash::LEVEL_ERROR);
        }

        return new Result(
            ['user' => $user],
            array_merge(
                ['action' => $action],
                $meta
            )
        );
    }

    /**
     * Gets the current user
     *
     * @return User
     */
    private function getCurrentUser()
    {
        return $this->session->get('user');
    }
}
