<?php
namespace LockSpace;

class RedisNewLock{

    public const SHARE = 0;
    public const UPDATE = 1;
    
    private  $redisClient = null;
 

    private  $client_number;


    private     $script = <<<script
    redis.replicate_commands()
    local key   =  KEYS[1]  
    local expire =  tonumber(ARGV[1]) 
    local type =  tonumber(ARGV[2]) 
    local a=redis.call('TIME') 
    local cur_timestamp =   tonumber( a[1])
    local result=0

    local client_name =  ARGV[3] 

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

    function  string.adecode(input)
            input = tostring(input)
            local arr =  string.split( input , "___")

            local res = {}

        for key, value in pairs( arr ) do    
            local a =  string.split( value, "???")
             res[a[1]] =  a[2] 
        end  
        return res

    end


    function string.aencode(input)
        local res1 = ""
      for key, value in pairs( input ) do    
        local str = key.."???"..value
        
        if(res1 ~= "" )
        then
            res1 = res1 .."___"..  str
        else
             res1 =   str
        end
      end  

      return  res1
    end


    local data_table   = redis.call('get',key ) 

 
    if( data_table ) then

      data_table =  string.adecode(data_table )

        data_table['type'] = tonumber(  data_table['type'] )  

         data_table['pass_time'] = tonumber(data_table['pass_time'] )

      if    cur_timestamp >=    data_table['pass_time']    then
         data_table  = {};

         data_table['type'] = type
         data_table['pass_time'] = expire + cur_timestamp
         data_table['update_time'] = cur_timestamp 

         result = 1
           
      else

        if(   data_table['type']   == 1) then
            result = 0
        else

          if(type == 0) then
             

           data_table['type'] = type
           data_table['pass_time'] = expire + cur_timestamp  

           data_table['share_count'] =   tonumber(  data_table['share_count'] ) + 1

           data_table['update_time'] = cur_timestamp 
           result = 1
          
          else

            if( tonumber( data_table['share_count'] ) <= 0) then

               data_table['type'] = type
               data_table['pass_time'] = expire + cur_timestamp 
               data_table['update_time'] = cur_timestamp 
            end
          end

          
        end

      end
    else 

       data_table  = {}

       data_table['type'] = type
       data_table['pass_time'] = expire + cur_timestamp
       data_table['update_time'] = cur_timestamp 

       if(type == 0) then
          data_table['share_count'] = 1
       end

       result = 1

    end

     data_table  =  string.aencode(    data_table  )
    

    if result == 1 then 
      local sadd_key = "sadd"..key 

      if redis.call('SADD', sadd_key , client_name ) and redis.call('setex',key,expire,  data_table)       then
                 return 1;
      end


        
      return 0
    end     
    return result
script;

    private     $unlock_script = <<<script
    local key   = KEYS[1]
 
     
    local client_name =  ARGV[1] 

    local sadd_key = "sadd"..key
  

    if  redis.call('SISMEMBER',sadd_key,client_name)  ==  0 then
                 return 0
    end

    local data_table   = redis.call('get',key ) 


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

    function  string.adecode(input)
            input = tostring(input)
            local arr =  string.split( input , "___")

            local res = {}

        for key, value in pairs( arr ) do    
            local a =  string.split( value, "???")
             res[a[1]] =  a[2] 
        end  
        return res

    end



    if(data_table)  then

        data_table =  string.adecode(data_table )
        data_table['type'] = tonumber(  data_table['type'] )  
             
        if( data_table['type']  == 0) then

            data_table['share_count'] = tonumber( data_table['share_count'] )  - 1

            if(   data_table['share_count'] <= 0) then
                    redis.call('DEL',sadd_key) 
                    return redis.call('DEL',key)      
            else
              
                redis.call('SREM',sadd_key,client_name)  
                data_table  =  string.aencode(    data_table  )
                redis.call('set',key,  data_table)    

            end
            return 1

        else
              redis.call('DEL',sadd_key) 
              redis.call('DEL',key)   
              return 1 
        end
      

   
    
    end

    return 0
script;

	public function __construct(  $redisClient )
	{
        $this->redisClient =  $redisClient ;

        $this->client_number = Random::str( 10 ).microtime();
        ///CLIENT SETNAME hello-world-connection

        $this->redisClient->executeRaw(['CLIENT SETNAME',$this->client_number]);

	}

	public function lock( $key,$expire,$type = RedisNewLock::SHARE ,$wait = 0)
	{
     
     $key = __CLASS__. $key;
     $wait = $wait * 1000 * 1000;;
	   $script_Arr = array('EVAL', $this->script, 1, $key, $expire,$type);
           $res = $this->eval( $script_Arr );
           if($res == 0)
           {
             	while($wait>0){
    		        $res = $this->eval( $script_Arr );
    		        if($res>0) 
                {
                  
                   return $res;
                } 

                             $wait  =  $wait - 1000 * 50;
                             sleep(1000 * 50); 
             	}
           }

      
           return $res;
	}


	public function unlock($key )
	{
      $key = __CLASS__. $key;
      return   $this->eval( array('EVAL', $this->unlock_script, 1, $key ) );
    
	}

  public function getUpdateLock( $key,$expire,$wait = 0)
  {
        return $this->lock( $key,$expire, RedisNewLock::UPDATE ,$wait  );
  }
	
  public function getShareLock( $key,$expire,$wait = 0)
  {
        return $this->lock( $key,$expire, RedisNewLock::SHARE ,$wait  );
  }


	private function eval(array $script_Arr)
	{
        array_push($script_Arr,$this->client_number);
	    return $this->redisClient->executeRaw($script_Arr);
	}
}
