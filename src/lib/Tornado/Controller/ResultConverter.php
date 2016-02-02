<?php

namespace Tornado\Controller;

use Twig_Environment;

use Negotiation\FormatNegotiator;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

use Tornado\Controller\Result;

/**
 * Converts Controller\Result to a Response object.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Controller
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class ResultConverter
{

    /**
     * Twig.
     *
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * Constructor.
     *
     * @param Twig_Environment $twig Twig.
     */
    public function __construct(Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Event listener called when a controller does not return a Response object.
     *
     * @param  GetResponseForControllerResultEvent $event The event.
     */
    public function onControllerResult(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult();

        // only take care of Tornado Controller Results
        if (!$controllerResult instanceof Result) {
            return;
        }

        $request = $event->getRequest();

        $response = $this->convertControllerResult($request, $controllerResult);

        $event->setResponse($response);
    }

    /**
     * Converts the passed data into a Response based on Request parameters.
     *
     * If HTTP_ACCEPT header expects `application/json` then it will wrap the `$result` in `JsonResponse`.
     *
     * Otherwise it will try to render a Twig template with the `$result` data passed to it. The template to render
     * is determined either by Request's `_template` attribute and if none found, then built based on controller name.
     *
     * @param  Request $request Request for which to render a response.
     * @param  Result  $result  Controller result.
     *
     * @return Response
     *
     * @throws \RuntimeException When could not determine template name.
     */
    public function convertControllerResult(Request $request, Result $result)
    {
        $formatNegotiator = new FormatNegotiator();
        $bestFormat = $formatNegotiator->getBest(
            $request->server->get('HTTP_ACCEPT'),
            ['application/json', 'text/html']
        );

        $responseData = $result->getData();
        if (!$responseData) {
            $responseData = new \StdClass;
        }

        $responseMetaData = $result->getMeta();
        if (!$responseMetaData) {
            $responseMetaData = new \StdClass;
        }

        $response = [ 'data' => $responseData, 'meta' => $responseMetaData];

        if ($bestFormat && $bestFormat->getValue() === 'application/json') {
            return new JsonResponse($response, $result->getHttpCode());
        }

        // maybe there is a template set explicitly?
        $template = $request->attributes->get('_template');

        // if no template set explicitly then build one based on controller name
        if (!$template) {
            $route = preg_replace('/^controller\./i', '', $request->attributes->get('_controller'));
            $template = str_replace(['.', ':'], '/', $route) . '.html.twig';
        }

        // if still didn't build it then throw exception
        if (!$template || $template === '.html.twig') {
            throw new \RuntimeException(
                'Could not determine what template to render. '
                . 'Have you set "_template" or "_controller" attribute in your route definition?'
            );
        }

        $content = $this->twig->render($template, $response);
        return new Response($content, $result->getHttpCode());
    }
}
