<?php
/**
 * Exception thrown when trying to update or delete an object that has not yet been
 * saved.
 *
 * @package Tornado
 * @subpackage DataMapper
 */
namespace Tornado\DataMapper\Exceptions;

use RuntimeException;

class UnsavedObjectException extends RuntimeException
{

}
