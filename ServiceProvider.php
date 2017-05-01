<?php

namespace Iris\Config\CRM;

use Iris\Config\CRM\sections\Email\Fetcher\FetcherFactory;

class ServiceProvider extends \Iris\ServiceProvider
{
    public function register()
    {
        parent::register();

        /**
         * Queue Serializer
         */
        $this->container
            ->register('email.fetcher_factory', FetcherFactory::class);
    }
}