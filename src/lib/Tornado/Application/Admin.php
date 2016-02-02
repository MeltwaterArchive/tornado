<?php

namespace Tornado\Application;

use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;

use DataSift\Http\Request;

use Tornado\Organization\Agency;

use Tornado\Application\Admin\RoutingExtension;

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
class Admin extends Tornado
{
    /**
     * Extends the basic Application providers of Tornado Application specific
     *
     * {@inheritdoc}
     */
    protected function registerProviders()
    {
        parent::registerProviders();

        $twig = $this->getContainer()->get('twig');
        $container = $this->getContainer();
        $twig->addExtension(
            new RoutingExtension(
                $this['url_generator'],
                $container->get('session')->get('user')
            )
        );
    }
}
