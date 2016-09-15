<?php
namespace Kemer\Pimple\Provider;

use Pimple\ServiceProviderInterface;
use Pimple\Container;

/**
 * Pimple container service provider.
 */
class Loggers implements ServiceProviderInterface
{
    private $services = [];
    /**
     * Instantiate the provider.
     * @param array $services
     */
    public function __construct(array $services = [])
    {
        if (empty($services)) {
            $services = include dirname(__DIR__)."/../../../config/loggers.config.php";
        }
        $this->services = $services;
    }

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple)
    {
        // register services and parameters on $pimple
        foreach ($this->services as $name => $service) {
            $pimple->offsetSet($name, $service);
        }
    }
}
