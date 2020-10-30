<?php
namespace Skrip42\Bundle\CacheLayerBundle;

use ProxyManager\Factory\AccessInterceptorValueHolderFactory as Factory;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Skrip42\Bundle\CacheLayerBundle\Annotations\Cache;
use Skrip42\Bundle\CacheLayerBundle\Annotations\AdditionalCache;
use Skrip42\Bundle\CacheLayerBundle\Exceptions\CacheLayerException;
use Skrip42\Bundle\CacheLayerBundle\CacheManager;
use Skrip42\Bundle\CacheLayerBundle\CacheAccessor;
use ReflectionClass;

class CacheLayerFactory
{
    protected $factory;
    protected $annotationReader;
    protected $container;

    public function __construct()
    {
        $this->factory = new Factory();
    }

    /** @required */
    public function setAnnotationReader(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    /** @required */
    public function setContainerInterface(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create(
        string $className,
        array  $methods,
        array  $arguments
    ) {
        $methods = $this->restoreCacher($methods);
        $instance = new $className(...$arguments);
        foreach ($methods as $method => $caches) {
            $cacheMap[$method] = [];
            foreach ($caches as $cache) {
                $cacheMap[$method][] = [
                    'cacher'    => $cache['cacher'],
                    'attr' => $cache['attr']
                ];
            }
        }
        $cacheAccessor = new CacheAccessor($instance, $cacheMap);
        CacheManager::addAccessor(get_class($instance), $cacheAccessor);
        $proxy = $this->factory->createProxy(
            $instance,
            $this->createPreCallArray($methods),
            $this->createPostCallArray($methods)
        );
        return $proxy;
    }

    private function restoreCacher(array $methods) : array
    {
        foreach ($methods as &$method) {
            foreach ($method as &$layer) {
                $layer['cacher'] = $this->container->get($layer['cacher']);
            }
        }
        return $methods;
    }

    private function createPreCallFunction($data)
    {
        return function (
            $proxy,
            $instance,
            $method,
            $params,
            &$returnEarly
        ) use (
            $data
        ) {
            for ($i = 0; $i < count($data); $i++) {
                $data[$i] = $this->prepareCacheData($data[$i], $params);
            }
            foreach ($data as $dat) {
                if (!in_array($dat['action'], ['clear'])) {
                    continue;
                }
                if (!$this->checkCondition($dat, $params)) {
                    continue;
                }
                $dat['cacher']->clear(
                    $instance,
                    $method,
                    $this->prepareParams($dat, $params),
                    $dat['attr']
                );
            }
            for ($i = 0; $i < count($data); $i++) {
                if (!in_array($data[$i]['action'], ['cache'])) {
                    continue;
                }
                if (!$this->checkCondition($data[$i], $params)) {
                    continue;
                }
                $preparedParams = $this->prepareParams($data[$i], $params);
                if ($data[$i]['cacher']->has(
                    $instance,
                    $method,
                    $preparedParams,
                    $data[$i]['attr']
                )) {
                    $returnEarly = true;
                    $return = $data[$i]['cacher']->get(
                        $instance,
                        $method,
                        $preparedParams,
                        $data[$i]['attr']
                    );
                    $i--;
                    while ($i >= 0) { //fill cache chain
                        if (in_array($data[$i]['action'], ['cache', 'actualize'])
                            && $this->checkCondition($data[$i], $params)
                        ) {
                            $data[$i]['cacher']->set(
                                $instance,
                                $method,
                                $preparedParams,
                                $return,
                                $data[$i]['attr']
                            );
                        }
                        $i--;
                    }
                    return $return;
                }
            }
        };
    }

    private function createPostCallFunction($data)
    {
        return function (
            $proxy,
            $instance,
            $method,
            $params,
            $returnValue,
            &$returnEarly
        ) use (
            $data
        ) {
            for ($i = 0; $i < count($data); $i++) {
                $data[$i] = $this->prepareCacheData($data[$i], $params);
            }
            foreach ($data as $dat) {
                if (!in_array($dat['action'], ['cache', 'actualize'])) {
                    continue;
                }
                if (!$this->checkCondition($dat, $params)) {
                    continue;
                }
                $dat['cacher']->set(
                    $instance,
                    $method,
                    $this->prepareParams($dat, $params),
                    $returnValue,
                    $dat['attr']
                );
            }
        };
    }

    private function checkCondition($data, $params)
    {
        if (!empty($data['condition'])) {
            foreach ($data['condition'] as $param => $value) {
                if (!isset($params[$param]) || $params[$param] != $value) {
                    return false;
                }
            }
        }
        return true;
    }

    private function prepareCacheData($data, $params)
    {
        if (!empty($data['clear_condition'])) {
            foreach ($data['clear_condition'] as $param => $value) {
                if (isset($params[$param]) && $params[$param] == $value) {
                    $data['action'] = 'clear';
                    return $data;
                }
            }
        }
        if (!empty($data['actualize_condition'])) {
            foreach ($data['actualize_condition'] as $param => $value) {
                if (isset($params[$param]) && $params[$param] == $value) {
                    $data['action'] = 'actualize';
                    return $data;
                }
            }
        }
        return $data;
    }

    private function prepareParams($data, $params)
    {
        foreach ($data['ignore_params'] as $ip) {
            unset($params[$ip]);
        }
        return $params;
    }

    private function createPreCallArray(
        array $methods
    ) :array {
        $result = [];
        foreach ($methods as $method => $data) {
            $result[$method] = $this->createPreCallFunction($data);
        }
        return $result;
    }

    private function createPostCallArray(
        array $methods
    ) :array {
        $result = [];
        foreach ($methods as $method => $data) {
            $result[$method] = $this->createPostCallFunction($data);
        }
        return $result;
    }
}
