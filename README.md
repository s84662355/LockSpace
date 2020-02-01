这是一个用redis实现分布式锁的项目
还有实现了令牌桶 

composer require chenjiahao/suospace


在app.php文件里加入  LockSpace\LockServiceProvider::class
例如


    'providers' => [
        LockSpace\LockServiceProvider::class,
 

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::clas


配置.env 配置LOCK_SPACE_REDIS  控制使用那个redis 默认使用default

这是一个独占锁

加锁
$key锁的名称 $expire过期时间 $wait 等待锁超时时间  都是以秒为单位
使用app('RedisLock')->lock( $key,$expire,$wait = 0)

解锁
$key锁的名称
app('RedisLock')->unlock($key)



 
获取一个独占锁
$key锁的名称 $expire过期时间 $wait 等待锁超时时间  都是以秒为单位
app('RedisNewLock')-> getUpdateLock( $key,$expire,$wait = 0)


获取一个共享锁
$key锁的名称 $expire过期时间 $wait 等待锁超时时间  都是以秒为单位
app('RedisNewLock')->getShareLock( $key,$expire,$wait = 0)


解锁
$key锁的名称
app('RedisNewLock')->unlock($key)


获取令牌 
$config['key'] 令牌名称
$config['max'] 令牌最大数量
$config['rate'] 每秒产生令牌数
$config['expire'] 令牌桶过期时间 默认 100秒
app('RateLimiter')->getToken(array $config)