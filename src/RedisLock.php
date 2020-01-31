<?php
namespace LockSpace;

class RedisLock{
    
    private  $redisClient = null;

   

    private $client_number ;

    private     $script = <<<script
    redis.replicate_commands();

    function string.split(input, delimiter)
        input = tostring(input)
        delimiter = tostring(delimiter)
        if (delimiter=='') then return false end
        local pos,arr = 0, {}
        -- for each divider found
        for st,sp in function() return string.find(input, delimiter, pos, true) end do
            table.insert(arr, string.sub(input, pos, st - 1))
            pos = sp + 1
        end
        table.insert(arr, string.sub(input, pos))
        return arr
    end
    

    local key   = KEYS[1]  
    local expire =  tonumber( ARGV[1] )
    local client_name =  ARGV[2] 
    local a = redis.call('TIME');

    local cur_timestamp =  tonumber( a[1]  )
    local result=0    

    local lockdata =  expire + cur_timestamp  
    lockdata = lockdata.. "???" .. client_name

    result = redis.call('setnx',key, lockdata) 
     

    if result == 0 then 

       local keydata = string.split(redis.call('get',key),'???')

       local time_out = tonumber(keydata[1])

       if cur_timestamp >  time_out then  
        


            time_out = expire + cur_timestamp

            lockdata =  time_out .. "???" .. client_name

            if redis.call('setex',key,expire,lockdata)   then 
              return    1
            end 
            
            

           return 0
       end 
       return 0  
    end  

    if     redis.call('Expire',key,expire)  then
            return 1
    end 
    
    return 0
script;

    private     $unlock_script = <<<script

    function string.split(input, delimiter)
        input = tostring(input)
        delimiter = tostring(delimiter)
        if (delimiter=='') then return false end
        local pos,arr = 0, {}
        -- for each divider found
        for st,sp in function() return string.find(input, delimiter, pos, true) end do
            table.insert(arr, string.sub(input, pos, st - 1))
            pos = sp + 1
        end
        table.insert(arr, string.sub(input, pos))
        return arr
    end



    local key   = KEYS[1]  

    local result = redis.call('get',key)
    local client_name =  ARGV[1] 

    if result == nil   then

       if redis.call('DEL',key) then 
            return  1
       end
       return 0
    end


    local keydata = string.split(result ,'???')

     

    if client_name  == keydata[2] and  redis.call('DEL',key)  then 

         return 1
    end

    return 0
     
script;

	public function __construct(  $redisClient )
	{
        $this->redisClient =  $redisClient ;
        $this->client_number = Random::str( 10 ).microtime();
        ///CLIENT SETNAME hello-world-connection

        //$this->redisClient->executeRaw(['CLIENT SETNAME',$this->client_number]);
	}

  //wait 单位是秒
	public function lock( $key,$expire,$wait = 0)
	{
     $key = __CLASS__. $key;
    
     $wait = $wait * 1000 * 1000;
	   $script_Arr = array('EVAL', $this->script, 1, $key, $expire);
           $res = $this->eval( $script_Arr );
           if($res == 0)
           {
             	while($wait>0){
    		         $res = $this->eval( $script_Arr );
    		         if($res>0) {
                       
                      return $res;
                 }
                   
                             $wait  =  $wait - 1000 * 50;
                             sleep(1000 * 50);
             	}
           }

       
           return $res;
	}


	public function unlock($key)
	{
      $key = __CLASS__. $key;
      $res = $this->eval( array('EVAL', $this->unlock_script, 1, $key) );
 
	    return   $res;
	}
	
	private function eval(array $script_Arr)
	{
      array_push($script_Arr,$this->client_number);
	    return $this->redisClient->executeRaw($script_Arr);
	}


}
