<?php

namespace Tornado\Application;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;

use DataSift\Silex\Application;
use DataSift\Http\Request;

use Tornado\Organization\Agency;
use Tornado\Security\Http\DataSiftApi\AuthenticationManager;

/**
 * Tornado API Application config
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual propferty rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Application
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Api extends Application
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
        $this->registerDataSiftApiAuthenticationEvent($container);
        $this->registerDataSiftUserLoadingEvent($container);

        // register response converter event listener
        $this->on(KernelEvents::VIEW, function ($event) use ($container) {
            $container->get('controller_result_converter')
                ->onControllerResult($event);
        });
    }

    /**
     * Registers API application unified error handling for not debug env
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
            $exception = $event->getException();
            $statusCode = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;

            $this->rootContainer->get('monolog')->error(
                $exception->getMessage() .
                $exception->getTraceAsString()
            );

            $event->setResponse(
                new JsonResponse(['error' => $exception->getMessage()], $statusCode)
            );
        });
    }

    /**
     * Registers user authentication event. This event is executed at the very beginning of application
     * dispatching. At this point, no routing is built.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    protected function registerDataSiftApiAuthenticationEvent(ContainerInterface $container)
    {
        // perform DataSift api key authentication only for routes defines "_auth_type" attribute
        $this->before(function (Request $request) use ($container) {
            $authType = $request->attributes->get('_auth_type', AuthenticationManager::TYPE_BRAND);

            try {
                $container->get('security.http.datasift_api.authentication_manager')
                    ->auth($request, $authType);
            } catch (\Exception $e) {
                $container->get('monolog')->error($e);
                $message = ($e->getMessage()) ? $e->getMessage() : 'Authorization failed';

                return new JsonResponse(['error' => $message], 401);
            }
        });
    }

    /**
     * Registers DataSift API auth credentials loading event. The priority of this event should equal default value.
     * Otherwise event will be triggered before Request attributes are set.
     *
     * This event must be triggered after DataSiftApiAuthenticationEvent in order to access
     * agency or brand data in request attributes which are sets during the authentication.
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
            $agency = $request->attributes->get('agency');

            $apiKey = $agency->getDatasiftApiKey();
            if ($brand = $request->attributes->get('brand')) {
                $apiKey = $brand->getDatasiftApiKey();
            }

            $container->set(
                'datasift.user',
                $container->get('datasift.api.user_provider')
                    ->setUsername($agency->getDatasiftUsername())
                    ->setApiKey($apiKey)
                    ->getInstance()
            );
        });
    }
}
