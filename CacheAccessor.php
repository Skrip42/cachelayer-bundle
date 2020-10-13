<?php

namespace Skrip42\Bundle\CacheLayerBundle;

use Skrip42\Bundle\CacheLayerBundle\Exceptions\CacheLayerException as Exception;

class CacheAccessor
{
    private $instance = null;
    private $cacheMap = [];

    public function __construct($instance, array $cacheMap)
    {
        $this->instance = $instance;
        $this->cacheMap = $cacheMap;
    }

    public function getLayer(string $cacheClassName) : CacheAccessor
    {
        $cacheMap = [];
        foreach ($this->cacheMap as $method => $caches) {
            foreach ($caches as $cache) {
                if (get_class($cache) !== $cacheClassName) {
                    continue;
                }
                if (empty($cacheMap[$method])) {
                    $cacheMap[$method] = [];
                }
                $cacheMap[$method][] = $cache;
            }
        }
        return new self($this->instance, $cacheMap);
    }

    public function getCacheMap() : array
    {
        return $this->cacheMap;
    }

    public function has(
        string $methodName,
        array $params = [],
        array $attr = []
    ) : bool {
        if (empty($this->cacheMap[$methodName])) {
            throw new Exception('method is undefined in the current layer');
        }
        foreach ($this->cacheMap[$methodName] as $cache) {
            if ($cache->has($this->instance, $methodName, $params, $attr)) {
                return true;
            }
        }
        return false;
    }

    public function find(
        string $methodName,
        array $params = [],
        array $attr = []
    ) : array {
        if (empty($this->cacheMap[$methodName])) {
            throw new Exception('method is undefined in the current layer');
        }
        $result = [];
        foreach ($this->cacheMap[$methodName] as $cache) {
            if ($cache->has($this->instance, $methodName, $params, $attr)) {
                $result[] = get_class($cache);
            }
        }
        return $result;
    }

    public function get(
        string $methodName,
        array $params = [],
        array $attr = []
    ) {
        if (empty($this->cacheMap[$methodName])) {
            throw new Exception('method is undefined in the current layer');
        }
        foreach ($this->cacheMap[$methodName] as $cache) {
            if ($cache->has($this->instance, $methodName, $params, $attr)) {
                return $cache->get($this->instance, $methodName, $params, $attr);
            }
        }
        return null;
    }

    public function set(
        string $methodName,
        $data = null,
        array $params = [],
        array $attr = []
    ) {
        if (empty($this->cacheMap[$methodName])) {
            throw new Exception('method is undefined in the current layer');
        }
        foreach ($this->cacheMap[$methodName] as $cache) {
            $cache->set($htis->instance, $methodName, $params, $data, $attr);
        }
    }

    public function clear(
        string $methodName,
        array $params = [],
        array $attr = []
    ) {
        if (empty($this->cacheMap[$methodName])) {
            throw new Exception('method is undefined in the current layer');
        }
        foreach ($this->cacheMap[$methodName] as $cache) {
            $cache->clear($this->instance, $methodName, $params, $attr);
        }
    }
}
