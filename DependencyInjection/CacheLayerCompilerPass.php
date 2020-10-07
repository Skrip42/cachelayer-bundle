<?php
namespace Skrip42\Bundle\CacheLayerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Skrip42\Bundle\CacheLayerBundle\CacheLayerFactory;

class CacheLayerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $cachedService = $container->findTaggedServiceIds('skrip42.cachelayer');
        foreach ($cachedService as $service => $value) {
            $defenition = $container->getDefinition($service);
            $arguments = $defenition->getArguments();
            $defenition->setFactory([
                new Reference(CacheLayerFactory::class),
                'create'
            ]);
            $defenition->setArguments(
                [
                    $defenition->getClass(),
                    $arguments
                ]
            );
        }
    }
}
