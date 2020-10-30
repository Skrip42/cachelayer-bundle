<?php
namespace Skrip42\Bundle\CacheLayerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Skrip42\Bundle\CacheLayerBundle\CacheLayerFactory;
use Skrip42\Bundle\CacheLayerBundle\CacheInterface;
use Skrip42\Bundle\CacheLayerBundle\CacheMapper;
use Skrip42\Bundle\CacheLayerBundle\Exceptions\CacheLayerException;
use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;

class CacheLayerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $cachedService = $container->findTaggedServiceIds('skrip42.cachelayer');
        $cacheMap = $container->getDefinition(CacheMapper::class);
        $map = [];
        foreach ($cachedService as $service => $value) {
            $defenition = $container->getDefinition($service);
            $defenition->setPublic(true);
            $arguments = $defenition->getArguments();
            $defenition->setFactory([
                new Reference(CacheLayerFactory::class),
                'create'
            ]);
            $additional = $this->getAdditionalCaches($defenition->getClass());
            $methods = $this->getMethodCaches($defenition->getClass());
            $cachemap = array_merge($additional, $methods);

            $map[$defenition->getClass()] = $defenition->getClass();
            $interfaces = class_implements($defenition->getClass());
            foreach ($interfaces as $interface) {
                $map[$interface] = $defenition->getClass();
            }

            $defenition->setArguments(
                [
                    $defenition->getClass(),
                    $cachemap,
                    $arguments
                ]
            );
        }
        $cacheMap->setArguments(
            [$map]
        );
    }

    private function getMethodCaches(string $className)
    {
        $methods = [];
        $reader = new AnnotationReader();
        $reflectionClass = new ReflectionClass($className);
        $reflectionMethods = $reflectionClass->getMethods();
        foreach ($reflectionMethods as $reflectionMethod) {
            $caches = $reader->getMethodAnnotations(
                $reflectionMethod,
                Cache::class
            );
            if (!empty($caches)) {
                $methodName = $reflectionMethod->name;
                $methods[$methodName] = [];
                foreach ($caches as $cache) {
                    $methods[$methodName][] = $this->prepareCacheAnnotation($cache);
                }
            }
        }
        return $methods;
    }

    private function getAdditionalCaches(string $className)
    {
        $reader = new AnnotationReader();
        $reflectionClass = new ReflectionClass($className);
        $caches = $reader->getClassAnnotations(
            $reflectionClass,
            AdditionalCache::class
        );
        $additional = [];
        if (!empty($caches)) {
            foreach ($caches as $additionalCaches) {
                $additional[$additionalCaches->name] = [];
                foreach ($additionalCaches->layers as $cache) {
                    $additional[$additionalCaches->name][] = $this->prepareCacheAnnotation($cache);
                }
            }
        }
        return $additional;
    }

    private function prepareCacheAnnotation($cache)
    {
        if (!in_array($cache->action, ['cache', 'clear'])) {
            throw new CacheLayerException(
                $cache->action . ' is not valid action '
                    . '("cache", "clear")'
            );
        }
        $cacheInterfaces = class_implements($cache->class);
        if (empty($cacheInterfaces[CacheInterface::class])) {
            throw new CacheLayerException(
                $cacher . ' is not '
                . CacheInterface::class . ' implementation;'
            );
        }

        return [
            'action'              => $cache->action,
            'cacher'              => $cache->class,
            'attr'                => $cache->attribute,
            'condition'           => $cache->condition,
            'actualize_condition' => $cache->actualize_condition,
            'clear_condition'     => $cache->clear_condition,
            'ignore_params'       => $cache->ignore_params
        ];
    }
}
