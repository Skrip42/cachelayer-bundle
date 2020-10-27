<?php
namespace Skrip42\Bundle\CacheLayerBundle\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 *
 * @Target({"METHOD", "ANNOTATION"})
 */
class Cache
{
    public $class;
    public $attribute = [];
    public $condition = [];
    public $actualize_condition = [];
    public $clear_condition     = [];

    /**
     * @Enum({"cache", "clear", "actualize"})
     */
    public $action = 'cache';
    public $ignore_params = [];
}
