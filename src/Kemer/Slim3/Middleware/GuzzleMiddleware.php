<?php
namespace Kemer\Slim3\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Exception as GuzzleException;

/**
 * http://www.slimframework.com/docs/concepts/middleware.html
 */
class GuzzleMiddleware
{
    /**
     * Slim middleware invokable class
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
        } catch (GuzzleException\ClientException $e) {
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
