<?php
namespace LockSpace;

class RedisLock{
    
    private  $redisClient = null;

    private     $script = <<<script
    local key   = KEYS[1]  
    local expire =  ARGV[1] 
    local a=redis.call('TIME') 
    local cur_timestamp =  a[1] 
    local result=0 
    result = redis.call('setnx',key,expire + cur_timestamp ) 
    if result == 0 then 
       local time_out = redis.call('get',key) 
       if cur_timestamp >  time_out then  
           return redis.call('setex',key,expire,expire + cur_timestamp)    
       end 
       return 0  
    end     
    return redis.call('Expire',key,expire) 
script;

    private     $unlock_script = <<<script
    local key   = KEYS[1]  
    return redis.call('DEL',key) 
script;



	public function __construct(  $redisClient )
	{
        $this->redisClient =  $redisClient ;
	}

	public function lock( $key,$expire,$wait = 0)
	{
         $res = Redis::executeRaw(array(
           'EVAL', $this->script, 1, $key, $expire
         ));

         if($res == 0)
         {
         	while($wait>0){
		         $res = $this->redisClient->executeRaw(array(
		           'EVAL', $this->script, 1, $key, $expire
		         ));
		         if($res>0) return $res;
                 $wait -- ;
                 sleep(1);
         	}
         }

         return $res;
	}


    private function unlock($key )
    {
        $res = $this->redisClient->executeRaw(array(
            'EVAL', $this->unlock_script, 1, $key
        ));

        return $res;
    }

}