<?php
/**
 * Test Bootstrap
 *
 * This software is the intellectual property of MediaSift Ltd., and is covered
 * by retained intellectual property rights, including copyright.
 * Distribution of this software is strictly forbidden under the terms of this license.
 *
 * File name     : bootstrap.php
 * Begin         : 2015-06-16
 * Description   : Application Bootstrap for Test
 *
 * @category   Services
 * @package    Tornado
 * @author     Nicola Asuni <nicola.asuni@datasift.com>
 * @copyright  2015-2015 MediaSift Ltd.
 * @license    http://mediasift.com/licenses/internal MediaSift Internal License
 * @link       https://github.com/datasift/ms-app-tornado
 */

// load the composer autoloader
require_once __DIR__.'/../src/vendor/autoload.php';

// set the maximum error reporting level
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('memory_limit', -1);
