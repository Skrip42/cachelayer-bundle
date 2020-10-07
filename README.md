# cachelayer-bundle
cache layer for services

## install:
- run `composer require skrip42/cachelayer-bundle`

## base usage:
implement cache interface:
```php
//redis cache example
namespace App\Services\Cache;

use Skrip42\Bundle\CacheLayerBundle\CacheInterface;
use App\Services\Redis;

class RedisCache implements CacheInterface
{
    private $client;

    /** @required */
    public function setRedisClient(...)
    {
        //inject you redis client
    }

    /**
     * Check is cache exist value
     *
     * @param mixed  $instance  request service instance
     * @param string $method    request method
     * @param array  $params    request params
     * @param array  $attr      custom attribute
     *
     * @return bool
     */
    public function has(
        $instance,
        string $method,
        array $params,
        array $attr
    ) : bool {
        return $this->client->exists(
            $this->getKey($instance, $method, $params)
        );
    }
    
    /**
     * Get value from cache
     *
     * @param mixed  $instance  request service instance
     * @param string $method    request method
     * @param array  $params    request params
     * @param array  $attr      custom attribute
     *
     * @return mixed
     */
    public function get(
        $instance,
        string $method,
        array $params,
        array $attr
    ) {
        return unserialize($this->client->get(
            $this->getKey($instance, $method, $params)
        ));
    }
    
    /**
     * Set data to cache
     *
     * @param mixed  $instance  request service instance
     * @param string $method    request method
     * @param array  $params    request params
     * @param mixed  $data      service return value
     * @param array  $attr      custom attribute
     */
    public function set(
        $instance,
        string $method,
        array $params,
        $data,
        array $attr
    ) {
        $ttl = empty($attr['ttl']) ? 0 : $attr['ttl'];
        $this->client->setex(
            $this->getKey($instance, $method, $params),
            $ttl,
            serialize($data)
        );
    }
    /**
     * Clear cache
     *
     * @param mixed  $instance  request service instance
     * @param string $method    request method
     * @param array  $params    request params
     * @param array  $attr      custom attribute
     */
    public function clear(
        $instance,
        string $method,
        array $params,
        array $attr
    ) {
        $pattern = get_class($instance) . '::' . $attr['target'] . '*';
        $keys = $this->client->keys($pattern);
        $this->client->del($keys);
    }

    public function getKey($instance, string $method, array $params) : string
    {
        return get_class($instance) . '::' . $method . '[' . serialize($params) . ']';
    }
}
```

make cache service is public:
```yaml
App\Services\Cache\RedisCache:
    public: true
```

tagged target service as cachable:
```yaml
App\Services\TargetService:
    tags: [skrip42.cachelayer]
```

add annotation to target service methods:
```php
     /**
      * @Cache(
      *      RedisCache::class,
      *      attribute = {
      *          "ttl" = 900
      *      }
      * )
      */
    public function foo(...) {
        //do something
    }
```
it uses the factory machinery, what breaks autowire controller parametrs
you must explicitly specify the constructor parameters or use ```/** @required */``` setters

## additional features:

### cache chain

you can add multiple cache annotations, caches will be executed in the specified order
```php
    /**
     * @Cache(LocalCache::class) //execute first
     * @Cache(RedisCache::class) //if LocalCache exist value, RedisCache will not be called
     */
    public function foo(...)
    {
        .....
```

### custom attribute
you can define additional attributes
that will be passed to all cache methods
```php
    /**
     * @Cache(
     *      RedisCache::class,
     *      attribute = {
     *          "ttl" = 900 //this attribute will be passed to all method of RedisCache
     *      }
     * )
     */
    public function foo(...)
    {
        .....
```

### conditional execution
You can specify a condition under which eesh will be executed
```php
    /**
     * @Cache(
     *      RedisCache::class,
     *      condition = {
     *          "nocache" = false // execute only if $nocache = false
     *      }
     * )
     */
    public function foo(bool $nocache = false)
```
### cache cleaner
You can specify the method for clearing the cache
```php
    /**
     * @Cache(
     *      RedisCache::class,
     *      action = "clear" //cache chear method will be called when foo is called
     * )
     */
    public function foo(...)
```
