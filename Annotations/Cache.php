<?php
namespace Skrip42\Bundle\CacheLayerBundle\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 *
 * @Target({"METHOD"})
 */
class Cache
{
    public $class;
    public $attribute = [];
    public $condition = [];
    public $reverse_condition = [];
    public $action    = 'cache';
    //public $value;
}
