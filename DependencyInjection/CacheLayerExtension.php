<?php

namespace Skrip42\Bundle\CacheLayerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
//use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Skrip42\Bundle\CacheLayerBundle\CacheLayerFactory;

class CacheLayerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    //public function process(ContainerBuilder $container)
    //{
        //$cachedService = $container->findTaggedServiceIds('skrip42.cache');
        //foreach ($cachedService as $service => $value) {
            //$defenition = $container->getDefinition($service);
            //$attr = $defenition->getTag('skrip42.cache')[0];
            //$arguments = $defenition->getArguments();
            //$defenition->setFactory([
                //new Reference(CacheLayerFactory::class),
                //'create'
            //]);
            //$defenition->setArguments(
                //[
                    //$defenition->getClass(),
                    //new Reference($attr['cache']),
                    //$attr,
                    //$arguments
                //]
            //);
        //}
    //}
}
