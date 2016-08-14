<?php
use Pimple\Container;

return [
    "gelf-host" => getenv("GRAYLOG_HOST"),

    "gelf-port" => getenv("GRAYLOG_PORT"),

    "logger" => function(Container $container) {
        $logger = new Monolog\Logger(getenv("PROJECT_NAME"));
        if (isset($container["amqp-broker"])) {
            $logger->pushHandler($container["gelf-amqp-handler"]);
        } elseif($container["gelf-host"] && $container["gelf-port"]) {
            $logger->pushHandler($container["gelf-tcp-handler"]);
        } else {
            $logger->pushHandler($container["error-log-handler"]);
        }
        return $logger;
    },

    "error-log-handler" => function(Container $container) {
        return new Monolog\Handler\ErrorLogHandler();
    },

    "gelf-tcp-handler" => function(Container $container) {
        $transport = new Gelf\Transport\TcpTransport("graylog.graylog.stream.weeb.online", 12203);
        $publisher = new Gelf\Publisher();
        $publisher->addTransport($transport);
        return new Monolog\Handler\GelfHandler($publisher);
    },

    "gelf-amqp-handler" => function(Container $container) {
        $broker = $container["amqpBroker"];
        $exchange = $broker->declareExchange(AMQP_DURABLE, 'log-messages', AMQP_EX_TYPE_FANOUT);
        $queue = $broker->queue(null, "log-messages");
        $publisher = new Gelf\Publisher();
        $publisher->addTransport(new Gelf\Transport\AmqpTransport($exchange, $queue));
        return new Monolog\Handler\GelfHandler($publisher);
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
