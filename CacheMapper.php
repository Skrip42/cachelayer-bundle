<?php
namespace Skrip42\Bundle\CacheLayerBundle;

class CacheMapper
{
    private $map;

    public function __construct(array $map)
    {
        $this->map = $map;
    }

    public function isset(string $className) : bool
    {
        return !empty($this->map[$className]);
    }

    public function get(string $className) : string
    {
        return $this->map[$className];
    }
}
