<?php
namespace Skrip42\Bundle\CacheLayerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Skrip42\Bundle\CacheLayerBundle\DependencyInjection\CacheLayerCompilerPass;

class CacheLayerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(
            new CacheLayerCompilerPass(),
            PassConfig::TYPE_BEFORE_REMOVING
        );
    }
}
