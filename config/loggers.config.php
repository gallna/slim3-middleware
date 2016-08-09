<?php
use Pimple\Container;

return [

    "logger" => function() {
        $logger = new Monolog\Logger(getenv("PROJECT_NAME"));
        $logger->pushHandler($handler = new Monolog\Handler\ErrorLogHandler());
        // $logger->warn("Some warning");
        return $logger;
    },

    "cliFormatter" => function(Container $container) {
        $formatter = new Monolog\Formatter\LineFormatter(
            $output = "\033[31m %level_name%\033[32m %message%\033[36m %context% \033[0m",
            $dateFormat = "g:i".
            false,
            false
        );
        $handler = $container["logger"]->popHandler();
        $handler->setFormatter($formatter);
        $container["logger"]->pushHandler($handler);
    },

    "newrelic.middleware" => function(Container $container) {
        return function ($request, $response, $next) {
            if (extension_loaded('newrelic')) {
                newrelic_set_appname(getenv("PROJECT_NAME"));
                newrelic_name_transaction(
                    $request->getAttribute('route')
                    ? $request->getAttribute('route')->getPattern()
                    : $request->getRequestTarget()
                );
            }
            return $next($request, $response);
        };
    },

    "bugsnag" => function(Container $container) {
        return Bugsnag\Client::make();
    },

    'disabled-phpErrorHandler' => function ($container) {
        return function ($request, $response, $error) use ($container) {
            if (extension_loaded('newrelic')) {
                newrelic_notice_error($error->getMessage(), $error);
            }
            if (getenv("BUGSNAG_API_KEY")) {
                $container["bugsnag"]->notifyException($error);
            }
            $handler = new Slim\Handlers\PhpError(
                $container["settings"]['displayErrorDetails']
            );
            return $handler->__invoke($request, $response, $error);
        };
    },

    "disabled-errorHandler" => function(Container $container) {
        return function ($request, $response, $exception) use ($container) {
            if (extension_loaded('newrelic')) {
                newrelic_notice_error($exception->getMessage(), $exception);
            }
            if (getenv("BUGSNAG_API_KEY")) {
                $container["bugsnag"]->notifyException($exception);
            }
            $handler = new Slim\Handlers\Error(
                $container["settings"]['displayErrorDetails']
            );
            return $handler->__invoke($request, $response, $exception);
        };
    },
];
