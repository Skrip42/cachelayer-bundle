<?php

namespace Skrip42\Bundle\CacheLayerBundle;

use ProxyManager\Factory\AccessInterceptorValueHolderFactory as Factory;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Skrip42\Bundle\CacheLayerBundle\Annotations\Cache;
use Skrip42\Bundle\CacheLayerBundle\Exceptions\CacheLayerException;
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

    private function getMethods(string $className)
    {
        $methods = [];
        $reflectionClass = new ReflectionClass($className);
        $reflectionMethods = $reflectionClass->getMethods();
        foreach ($reflectionMethods as $reflectionMethod) {
            $caches = $this->annotationReader->getMethodAnnotations(
                $reflectionMethod,
                Cache::class
            );
            if (!empty($caches)) {
                $methodName = $reflectionMethod->name;
                $methods[$methodName] = [];
                foreach ($caches as $cache) {
                    if (!in_array($cache->action, ['cache', 'clear'])) {
                        throw new CacheLayerException(
                            $cache->action . ' is not valid action '
                                . '("cache", "clear")'
                        );
                    }
                    $cacher = $this->container->get($cache->class);
                    if (!$cacher instanceof CacheInterface) {
                        throw new CacheLayerException(
                            get_class($cacher) . ' is not '
                            . CacheInterface::class . ' implementation;'
                        );
                    }
                    $methods[$methodName][] = [
                        'action'              => $cache->action,
                        'cacher'              => $cacher,
                        'attr'                => $cache->attribute,
                        'condition'           => $cache->condition,
                        'actualize_condition' => $cache->actualize_condition,
                        'clear_condition'     => $cache->clear_condition,
                        'ignore_params'       => $cache->ignore_params
                    ];
                }
            }
        }
        return $methods;
    }

    public function create(
        string $className,
        array $arguments
    ) {
        $methods = $this->getMethods($className);
        $proxy = $this->factory->createProxy(
            new $className(...$arguments),
            $this->createPreCallArray($methods),
            $this->createPostCallArray($methods)
        );
        return $proxy;
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
            foreach ($data as &$dat) {
                $dat = $this->prepareCacheData($dat, $params);
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
            foreach ($data as &$dat) {
                $dat = $this->prepareCacheData($dat, $params);
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
