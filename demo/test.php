<?php
require dirname(__DIR__) .'/vendor/autoload.php';
use  Predis\Client;
use  LockSpace\RedisRateLimiter;
use LockSpace\RedisLock;


$client = new Predis\Client();

$limiter = new RedisRateLimiter($client);

$config['key'] = 'sfsdfsd';
 $config['rate'] =10;
 $config['max'] =50;
 $config['default']=10;



///var_dump($limiter->getTicket($config)) ;


$cli = new RedisLock($client );

var_dump($cli->lock('dda',10)) ;


