<?php
namespace Kemer\Slim3\Exceptions;

/**
 * Exception when a client error is encountered (4xx codes)
 */
class HttpException extends \Exception implements HttpExceptionIterface
{
    public static function illegalInput($message, \Exception $e = null)
    {
        return new IllegalInputException($message, 400, $e);
    }

    public static function notFound($message, \Exception $e = null)
    {
        return new NotFoundException($message, 404, $e);
    }

    public static function contentType($message, \Exception $e = null)
    {
        return new ContentTypeException($message, 406, $e);
    }

    public function __construct(\Exception $e = null)
    {
        parent::__construct($e->getMessage(), $e->getCode(), $e);
    }
}
