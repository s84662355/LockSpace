<?php
namespace LockSpace;

class RedisRateLimiter{

	private  $redisClient = null;

    private    $lua = <<<LUA
        local sKey = KEYS[1];
        local nKey = KEYS[2];
        local now = tonumber(ARGV[1]);
        local rate = tonumber(ARGV[2]);
        local max = tonumber(ARGV[3]);
        local default = tonumber(ARGV[4]);
        
        local sNum = redis.call('get', sKey);
        if((not sNum) or sNum == nil)
        then
            sNum = 0
        end
        
        sNum = tonumber(sNum);
        
        local nNum = redis.call('get', nKey);
        if((not nNum) or nNum == nil)
        then
            nNum = now
            sNum = default
        end
        
        nNum = tonumber(nNum);
        
        local newPermits = 0;
        if(now > nNum)
        then
              newPermits = (now-nNum)*rate+sNum;
              sNum = math.min(newPermits, max)
        end
        
        local isPermited = 0;
        if(sNum > 0)
        then
            sNum = sNum -1;
            isPermited = 1;
        end
        
        redis.call('set', sKey, sNum);
        redis.call('set', nKey, now);
        
        return isPermited;
LUA;


	public function __construct(  $redisClient )
	{
        $this->redisClient =  $redisClient ;
	}


    /**
     * @param array $config
     *
     * @return bool
     * @throws RedisException
     */
    public function getTicket(array $config): bool
    {
        $name = $config['name'];
        $key  = $config['key'];

        $now  = time();
        $sKey = $this->getStorekey($name, $key);
        $nKey = $this->getNextTimeKey($name, $key);

        $rate    = $config['rate'];
        $max     = $config['max'];
        $default = $config['default'];

 

        $args = [
            'EVAL',
            $this->lua,
            2,
            $sKey,
            $nKey,
            $now,
            $rate,
            $max,
            $default,
        ];

        $result =  $this->eval( $args );

       
        return (bool)$result;
    }

    /**
     * @param string $name
     * @param string $key
     *
     * @return string
     */
    private function getNextTimeKey(string $name, string $key): string
    {
        return sprintf('%s:%s:next', $name, $key);
    }

    /**
     * @param string $name
     * @param string $key
     *
     * @return string
     */
    private function getStorekey(string $name, string $key): string
    {
        return sprintf('%s:%s:store', $name, $key);
    }

	private function eval(array $script_Arr)
	{
	    return $this->redisClient->executeRaw($script_Arr);
	}

}