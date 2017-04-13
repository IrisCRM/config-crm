<?php

namespace Iris\Config\CRM\Service\Lock;

use Iris\Iris;
use malkusch\lock\mutex\FlockMutex;

class MutexFactory
{
    const LOCK_DIR = 'lock' . DIRECTORY_SEPARATOR;

    /**
     * @param string|null $resource Identifier of resource for lock
     */
    public static function create($resource = null)
    {
        if (!$resource) {
            $resource = '00000000-0000-0000-0000-000000000000';
        }

        $lockDir = Iris::$app->getTempDir() . static::LOCK_DIR;
        if (!is_dir($lockDir)) {
            mkdir($lockDir);
        }

        $lockFile = $lockDir . $resource;
        if (!is_file($lockFile)) {
            touch($$lockFile);
        }

        return new FlockMutex(fopen($lockFile, "r"));
    }
}