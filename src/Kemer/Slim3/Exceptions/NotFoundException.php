<?php
namespace Kemer\Slim3\Exceptions;

/**
 * Exception when a client error is encountered (4xx codes)
 */
class NotFoundException extends \Exception implements HttpExceptionIterface {}
