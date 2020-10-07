<?php
namespace Skrip42\Bundle\CacheLayerBundle;

interface CacheInterface
{
    public function has(
        $instance,
        string $methodName,
        array $params,
        array $attr
    ) : bool;
    public function get(
        $instance,
        string $methodName,
        array $params,
        array $attr
    );
    public function set(
        $instance,
        string $methodName,
        array $params,
        $data,
        array $attr
    );
    public function clear(
        $instance,
        string $methodName,
        array $params,
        array $attr
    );
}
