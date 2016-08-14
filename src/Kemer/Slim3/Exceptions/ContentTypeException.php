<?php
namespace Kemer\Slim3\Exceptions;

/**
 * Exception when a client error is encountered (4xx codes)
 */
class ContentTypeException extends \Exception implements HttpExceptionIterface {}
