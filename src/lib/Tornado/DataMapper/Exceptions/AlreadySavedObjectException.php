<?php
/**
 * Exception thrown when trying to save an object that has already been saved.
 *
 * @package Tornado
 * @subpackage DataMapper
 */
namespace Tornado\DataMapper\Exceptions;

use RuntimeException;

class AlreadySavedObjectException extends RuntimeException
{

}
