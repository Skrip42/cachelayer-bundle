<?php
namespace Skrip42\Bundle\CacheLayerBundle;

class CacheManager
{
    private static $store;

    public static function addAccessor(string $className, CacheAccessor $accessor)
    {
        static::$store[$className] = $accessor;
    }

    public static function getBy(string $className) : ?CacheAccessor
    {
        foreach (static::$store as $accessor) {
            if ($accessor->isStatisfiedBy($className)) {
                return $accessor;
            }
        }
    }
}
