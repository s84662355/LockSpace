<?php
namespace LockSpace;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
class LockServiceProvider  extends ServiceProvider
{
 

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('RedisLock', function ($app) {
        	return new RedisLock($app->make('redis'));
        });


        $this->app->singleton('RateLimiter', function ($app) {
            return new RedisRateLimiter($app->make('redis'));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
         
    }
}