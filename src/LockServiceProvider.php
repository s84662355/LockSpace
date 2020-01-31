<?php
namespace LockSpace;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
class LockServiceProvider  extends ServiceProvider
{
 
    /**
     * 是否延时加载提供器。
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $redis = app()->make('redis')->connection(env('LOCK_SPACE_REDIS','default'));
         
        $this->app->singleton('RedisLock', function ($app) use($redis){
        	return new RedisLock($redis);
        });


        $this->app->singleton('RateLimiter', function ($app)  use($redis){
            return new RedisRateLimiter($redis);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
         return ['RedisLock', 'RateLimiter'];   
    }
}