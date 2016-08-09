<?php
namespace Kemer\Slim3\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Http\Client\Exception\HttpException;
/**
 * http://www.slimframework.com/docs/concepts/middleware.html
 */
class PhpHttpMiddleware
{
    /**
     * http://php-http.org/en/latest/index.html
     *
     * @param  ServerRequestInterface $request PSR7 request
     * @param  ResponseInterface $response PSR7 response
     * @param  callable $next Next middleware
     *
     * @return ResponseInterface
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        try {
            return $next($request, $response);
        } catch (HttpException $e) {
            // Exception when a client error is encountered (4xx codes)
            // Added Access-Control headers - since nginx add_header doesn't do it for 404 code
            return $response
                ->withStatus($e->getCode())
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type')
                ->withJson(["code" => $e->getCode(), "message" => $e->getMessage()]);
        }
    }
}
