<?php
require dirname(__DIR__) .'/vendor/autoload.php';
use  Predis\Client;
use  LockSpace\RedisRateLimiter;
use LockSpace\RedisLock;
use LockSpace\RedisNewLock;


$client = new Predis\Client();
 


$cli = new RedisNewLock($client );

//var_dump($cli->lock('dda',10,1)) ;

var_dump($cli->getUpdateLock('dda11',10)) ;


 var_dump($cli->unlock('dda11'));
 
var_dump($cli->getShareLock('dda11',10)) ;

//var_dump($cli->unlock('dda11'));
//var_dump($cli->unlock('dda',0));


 