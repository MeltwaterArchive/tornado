<?php

namespace Tornado\Application;

use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

use DataSift\Silex\Application;
use DataSift\Http\Request;

use Tornado\Organization\Agency;
use Tornado\Organization\User\UserTemplateViewObject;
use Tornado\Application\Flash\ServiceProvider as FlashServiceProvider;

/**
 * Tornado Application config
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Application
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Tornado extends Application
{
    /**
     * Extends the basic Application constructor of Tornado Application config
     *
     * {@inheritdoc}
     */
    public function __construct($container, array $values = [])
    {
        parent::__construct($container, $values);

        $this->registerEvents();
    }

    /**
     * Extends the basic Application providers of Tornado Application specific
     *
     * {@inheritdoc}
     */
    protected function registerProviders()
    {
        parent::registerProviders();

        $container = $this->getContainer();

        // session
        $this->registerSessionProvider($container);

        // flash
        $this->registerFlashProvider($container);

        $twigOptions = ($container->hasParameter('twig.options')) ? $container->getParameter('twig.options') : [];
        
        // twig
        $this->register(new TwigServiceProvider(), [
            'twig.path' => $container->getParameter('twig.path'),
            'twig.options' => $twigOptions
        ]);
        $container->set('twig', $this['twig']);

        $this->registerClientSide($container);
        $this->registerKissmetrics($container);
        $this->registerZendesk($container);
        $this->registerGa($container);
    }

    /**
     * Registers and configures the Session provider
     *
     * @param ContainerInterface $container
     */
    protected function registerSessionProvider(ContainerInterface $container)
    {
        $sessionConfig = [];

        if ($container->hasParameter('session.storage.save_path')) {
            $sessionConfig['session.storage.save_path'] = $container->getParameter('session.storage.save_path');
        }

        if ($container->hasParameter('session.storage.options')) {
            $sessionConfig['session.storage.options'] = $container->getParameter('session.storage.options');
        }

        $this->register(new SessionServiceProvider(), $sessionConfig);
        $this['session.storage.handler'] = $container->get('session.storage.handler');
        $container->set('session', $this['session']);
    }

    /**
     * Registers the flash message provider
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    protected function registerFlashProvider(ContainerInterface $container)
    {
        $flash = new FlashServiceProvider();
        $this->register($flash, []);
        $container->set('flash', $flash);
    }

    /**
     * Loads this Application events.
     */
    protected function registerEvents()
    {
        $container = $this->getContainer();

        // set Request as a DIC service. Keep in mind that it is \DataSift\Http\Request rather than
        // Symfony one.
        $this->before(function (Request $r) use ($container) {
            $container->set('request', $r);
        }, 9999);

        $this->registerErrorHandlingEvent();
        $this->registerTornadoAuthenticationEvent($container);
        $this->registerAcl($container);
        $this->registerDataSiftUserLoadingEvent($container);

        // register response converter event listener
        $this->on(KernelEvents::VIEW, function ($event) use ($container) {
            $container->get('controller_result_converter')
                ->onControllerResult($event);
        });
    }

    /**
     * Registers application unified error handler for not debug env
     */
    protected function registerErrorHandlingEvent()
    {
        if ($this->rootContainer->getParameter('debug')) {
            return;
        }

        // registers error, exception handlers
        ErrorHandler::register();
        ExceptionHandler::register();

        $this->on(KernelEvents::EXCEPTION, function ($event) {
            /** @var $event \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent */
            $exception = $event->getException();

            $code = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;

            $this->rootContainer->get('monolog')->error(
                $exception->getMessage() .
                $exception->getTraceAsString()
            );

            $message = $exception->getMessage();

            if ($event->getRequest()->isXmlHttpRequest()) {
                $event->setResponse(new \Symfony\Component\HttpFoundation\JsonResponse(['error' => $message], $code));
            } else {
                $content = $this->rootContainer->get('twig')->render(
                    $this->rootContainer->getParameter('template.error'),
                    [
                        'statusCode' => $code,
                        'errorMessage' => $message
                    ]
                );
                $event->setResponse(new Response($content, $code));
            }
        });
    }

    /**
     * Registers user authentication event. This event is executed at the very beginning of application
     * dispatching. At this point, no routing is built.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function registerTornadoAuthenticationEvent(ContainerInterface $container)
    {
        // perform user authentication against app firewalls
        $this->before(function (Request $request) use ($container) {
            $firewallDecision = $container->get('security.http.authentication_firewall')
                ->isGranted();
            $sessionUser = $container->get('session')
                ->get('user');

            $twig = $container->get('twig');
            // if null, firewall granted an access and we can set the authUser as global for twig
            if (!$firewallDecision && $sessionUser) {
                $twig->addGlobal(
                    'sessionUser',
                    new UserTemplateViewObject($sessionUser)
                );

                $twig->addGlobal(
                    'organization',
                    $container->get('session.user.organization')
                );
            }

            $twig->addGlobal(
                'flash',
                $container->get('flash')
            );

            $twig->addGlobal('base_url', $container->getParameter('twig.base_url'));

            return $firewallDecision;
        });
    }

    /**
     * Registers DataSift API auth credentials loading event. The priority of this event should equal default value.
     * Otherwise event will be triggered before Request attributes are set.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function registerDataSiftUserLoadingEvent(ContainerInterface $container)
    {
        // load authenticated user DS credentials
        $this->on(KernelEvents::REQUEST, function ($event) use ($container) {
            $request = $container->get('request');
            $brand = $container->get('organization.brand.http.resolver')
                ->resolve($request);

            // do not throw exception, lets allow Users play with Tornado app.
            // DS api will take care of throwing an error when applicable
            if (!$brand) {
                return;
            }

            $agency = $container->get('organization.agency.repository')
                ->findOne(['id' => $brand->getAgencyId()]);

            // sets agency Tornado app theme
            $request->attributes->set('skin', $agency->getSkin());

            $datasiftUsername = empty($brand->getDatasiftUsername())
                ? $agency->getDatasiftUsername()
                : $brand->getDatasiftUsername();

            $container->set(
                'datasift.user',
                $container->get('datasift.api.user_provider')
                    ->setUsername($datasiftUsername)
                    ->setApiKey($brand->getDatasiftApiKey())
                    ->getInstance()
            );
        });
    }

    /**
     * Registers application ACL based on routes "_permissions" property.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function registerAcl(ContainerInterface $container)
    {
        $this->on(KernelEvents::REQUEST, function ($event) use ($container) {
            if (!$container->get('session.user')) {
                return;
            }

            if (!$container->get('security.http.acl_firewall')->isGranted()) {
                throw new AccessDeniedHttpException('You are not granted to access this resource.');
            }
        });
    }

    protected function registerClientSide(ContainerInterface $container)
    {
        $twig = $container->get('twig');

        if ($container->hasParameter('cs.build.location')) {
            $twig->addGlobal('cs_build_location', $container->getParameter('cs.build.location'));
        }

        if ($container->hasParameter('cs.javascript.location')) {
            $twig->addGlobal('cs_javascript_location', $container->getParameter('cs.javascript.location'));
        }

        if ($container->hasParameter('cs.bower.location')) {
            $twig->addGlobal('cs_bower_location', $container->getParameter('cs.bower.location'));
        }
    }

    /**
     * Registers the Kissmetrics key in Twig
     *
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    protected function registerKissmetrics(ContainerInterface $container)
    {
        if ($container->hasParameter('kissmetrics.key')) {
            $twig = $container->get('twig');
            $twig->addGlobal('kissmetrics_key', $container->getParameter('kissmetrics.key'));
        }
    }

    /**
     * Registers the Zendesk key in Twig
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    protected function registerZendesk(ContainerInterface $container)
    {
        if ($container->hasParameter('zendesk.url')) {
            $twig = $container->get('twig');
            $twig->addGlobal('zendesk_url', $container->getParameter('zendesk.url'));
        }
    }

    /**
     * Registers the GA key in Twig
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    protected function registerGa(ContainerInterface $container)
    {
        if ($container->hasParameter('ga.key')) {
            $twig = $container->get('twig');
            $twig->addGlobal('ga_key', $container->getParameter('ga.key'));
        }
    }
}
