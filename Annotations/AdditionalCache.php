<?php
namespace Skrip42\Bundle\CacheLayerBundle\Annotations;

use Doctrine\Common\Annotations\Annotation;
use Skrip42\Bundle\CacheLayerBundle\Annotations\Cache;

/**
 * @Annotation
 *
 * @Target({"CLASS"})
 */
class AdditionalCache
{
    public $name;
    /** @var array<Skrip42\Bundle\CacheLayerBundle\Annotations\Cache> */
    public $layers;
}
