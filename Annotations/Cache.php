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
    public $actualize_condition = [];
    public $clear_condition     = [];
    public $action = 'cache';
    public $ignore_params = [];
}
