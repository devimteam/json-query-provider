<?php

namespace Devim\Provider\JsonQueryServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Class JsonQueryServiceProvider.
 */
class JsonQueryServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $container A container instance
     */
    public function register(Container $container)
    {
        $container['json_query.service'] = function () {
            return new JsonQueryService();
        };
    }
}
