<?php
/**
 * Front Controller for API app.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     Tornado
 * @author      Michael Heap <michael.heap@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */

// load the composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// log microtime of the request, so we can measure render time
DataSift\Http\StatsCollector::$requestTime = microtime(true);

$env = getenv('APP_ENV') ?: 'production';
$configPath = realpath(__DIR__ . '/../config/api');
$resourcesPath = (is_dir('/etc/tornado')) ? '/etc/tornado' : realpath(__DIR__ . '/../../resources/config');

// Catches Silex early errors which can not be handle by Silex Application Error Hanlder
try {
    $bootstrap = new \DataSift\Silex\Bootstrap($configPath, $resourcesPath, $env);

    // Build the container
    $container = $bootstrap->buildContainer();

    // Create an Application
    $app = $bootstrap->createApplication(\Tornado\Application\Api::class, $container);
} catch (\Exception $e) {
    $filename = (file_exists("{$resourcesPath}/parameters.yml")) ? "{$resourcesPath}/parameters.yml" : "{$resourcesPath}/{$env}/parameters.yml";
    if (!file_exists($filename)) {
        echo "Could not load local parameters.yml from {$filename}";
        exit(1);
    }
    $parameters = \Symfony\Component\Yaml\Yaml::parse($filename);
    $logFile = str_replace('%env%', $env, $parameters['parameters']['monolog.log_file']);
    $error = sprintf("%s\n\n%s", $e->getMessage(), $e->getTraceAsString());

    error_log('[' . date("Y-m-d, G:i:s") . '] tornado.api.ERROR: ' . $error . "\n", 3, $logFile);

    if ('production' === $env) {
        header('Content-Type: appliction/json');
        $error = json_encode(['error' => $parameters['parameters']['api.error.default']]);
    }

    echo $error;
    exit(1);
}

$app->run();
