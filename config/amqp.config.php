<?php
use Pimple\Container;
use Kemer\Amqp;
use Kemer\Amqp\Addons as AmqpAddons;

return [
    "amqp-queue-name" => getenv("AMQP_QUEUE_NAME"),
    "amqp-queue-flags" => getenv("AMQP_QUEUE_FLAGS") ?: AMQP_DURABLE,
    "amqp-exchange-name" => getenv("AMQP_EXCHANGE_NAME"),
    "amqp-exchange-type" => getenv("AMQP_EXCHANGE_TYPE") ?: AMQP_EX_TYPE_TOPIC,
    "amqp-exchange-flags" => getenv("AMQP_EXCHANGE_FLAGS") ?: AMQP_DURABLE,
    "amqp-dead-letter-name" => getenv("AMQP_DEAD_LETTER_NAME"),
    "amqp-publish-exchange-name" => getenv("AMQP_PUBLISH_EXCHANGE_NAME"),
    "amqp-publish-exchange-type" => getenv("AMQP_PUBLISH_EXCHANGE_TYPE") ?: AMQP_EX_TYPE_TOPIC,
    "amqp-broker" => [
        'host' => getenv("AMQP_HOST") ?: 'rabbit.docker',
        'port' => getenv("AMQP_PORT") ?: 5672,
        'login' => getenv("AMQP_LOGIN") ?: 'guest',
        'password' => getenv("AMQP_PASSWORD") ?: 'guest'
    ],

    "dispatcher" => function (Container $container) {
        $container["amqpLogger"];
        $container["amqpPublishers"];
        return $container["amqpDispatcher"];
    },

    "consumer" => function (Container $container) {
        $container["amqpAddons"];
        $container["amqpCommands"];
        $container["amqpErrorHandler"];
        return $container["dispatcher"];
    },

    "amqpBroker" => function (Container $container) {
        return new Amqp\Broker(...array_values($container["amqp-broker"]));
    },

    "amqpDispatcher" => function (Container $container) {
        return new Amqp\Dispatcher();
    },

    "amqpConsumer" => function (Container $container) {
        return new Amqp\Consumer($container["amqpDispatcher"]);
    },

    "amqpExchange" => function (Container $container) {
        return $container["amqp-exchange-name"]
            ? $container['amqpBroker']->declareExchange(
                $container["amqp-exchange-flags"],
                $container["amqp-exchange-name"],
                $container["amqp-exchange-type"]
            ) : null;
    },

    "amqpQueue" => function (Container $container) {
        $queue = $container['amqpBroker']->queue(
            $container["amqp-queue-flags"],
            $container["amqp-queue-name"]
        );
        if ($container["amqp-dead-letter-name"]) {
            $queue->setArgument("x-dead-letter-exchange", $container["amqp-dead-letter-name"]);
        }
        $queue->declareQueue();
        return $queue;
    },

    "amqpLogger" => function (Container $container) {
        if (isset($container["logger"])) {
            $container["amqpDispatcher"]
                ->addSubscriber(new AmqpAddons\MonologSubscriber($container["logger"]));
        }
        if (isset($container["cliFormatter"])) {
            $container["cliFormatter"];
        }
    },

    "amqpPublishers" => function (Container $container) {
        $container["amqpDispatcher"]
            ->addListener("#", new Amqp\Publisher\ExchangePublisher($container["amqpBroker"]), 1001);
        $container["amqpDispatcher"]
            ->addListener("#", new Amqp\Publisher\QueuePublisher($container["amqpBroker"]), 1000);
        $container["amqpDefaultPublisher"];
    },

    "amqpDefaultPublisher" => function (Container $container) {
        if ($publishExchange = $container["amqp-publish-exchange-name"]) {
            $exchangeType = $container["amqp-publish-exchange-type"];
            $container["amqpDispatcher"]
                ->addListener("#", new Amqp\Publisher\DefaultExchangePublisher(
                    $container["amqpBroker"]->declareExchange(AMQP_DURABLE, $publishExchange, $exchangeType),
                    $container["amqpBroker"]),
                    1001
            );
        }
    },

    "amqpErrorHandler" => function (Container $container) {
        $container["amqpDispatcher"]
            ->addListener('kemer.error', function (GenericEvent $event, $eventName, $dispatcher) {
                $subject = $event->getSubject();
                if (!$subject->isConsumed()) {
                    $subject->reject($subject->isRedelivery() ? false : AMQP_REQUEUE);
                }
            }, -2);
    },

    "amqpCommands" => function (Container $container) {
        $container["amqpDispatcher"]
            ->addSubscriber(new AmqpAddons\Command\QueueGetCommand($container["amqpBroker"]));
    },

    "amqpAddons" => function (Container $container) {
        $container["amqpDispatcher"]
            ->addSubscriber(new AmqpAddons\PostponeSubscriber($container["amqpBroker"]));
        $container["amqpDispatcher"]
            ->addSubscriber(new AmqpAddons\DeadLetterSubscriber($container["amqpBroker"]));
    },
];
