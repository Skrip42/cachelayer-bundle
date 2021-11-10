<?php
namespace Skrip42\Bundle\CacheLayerBundle;

use Skrip42\Bundle\CacheLayerBundle\Exceptions\CacheLayerException as Exception;

class CacheManager
{
    private static $store;
    private static $map;

    public static function addAccessor(string $className, CacheAccessor $accessor)
    {
        static::$store[$className] = $accessor;
    }

    public static function getBy(string $className) : ?CacheAccessor
    {
        global $kernel;
        if (empty(static::$map)) {
            static::$map = $kernel->getContainer()->get(CacheMapper::class);
        }
        if (!static::$map->isset($className)) {
            throw new Exception($className . ' is not cacheble service');
        }
        $className = static::$map->get($className);
        if (empty(static::$store[$className])) {
            $kernel->getContainer()->get($className); //init depend service
        }
        return static::$store[$className];
    }
}
