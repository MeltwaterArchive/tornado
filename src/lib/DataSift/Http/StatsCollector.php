<?php

namespace DataSift\Http;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;

use DataSift\Stats\Collector as BaseStatsCollector;

/**
 * Collects stats for HTTP requests and responses.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Http
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class StatsCollector
{
    /**
     * Stores request start time.
     *
     * Static, because we want to log it as early as possible, way before container and bootstrap process.
     *
     * Set as `microtime(true)`
     *
     * @var float
     */
    public static $requestTime;

    /**
     * Stats service.
     *
     * @var DataSift\Stats\Collector
     */
    protected $stats;

    /**
     * Constructor.
     *
     * @param DataSift\Stats\Collector $stats Stats service.
     */
    public function __construct(BaseStatsCollector $stats)
    {
        $this->stats = $stats;
    }

    /**
     * Triggered when a request comes in.
     *
     * @param KernelEvent $event Kernel event.
     */
    public function onRequest(KernelEvent $event)
    {
        $request = $event->getRequest();

        $this->stats->increment('request');
        $this->stats->increment('request.method.'. strtolower($request->getMethod()));
    }

    /**
     * Triggered when an exception is unhandled somewhere.
     *
     * @param GetResponseForExceptionEvent $event Exception event.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onException(GetResponseForExceptionEvent $event)
    {
        $this->stats->increment('exception');
    }

    /**
     * Triggered when the response is about to be sent.
     *
     * @param FilterResponseEvent $event Response event.
     */
    public function onResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();

        $this->stats->increment('response');
        $this->stats->increment('response.code.'. $response->getStatusCode());

        // track render time
        if (isset(self::$requestTime)) {
            $now = microtime(true);
            $elapsed = $now - self::$requestTime;
            $this->stats->addTiming('render_time', $elapsed);
        }
    }
}
