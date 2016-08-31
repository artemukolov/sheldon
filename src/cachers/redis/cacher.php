<?php
    namespace Sheldon\cacher;

    class redis
    {
        private static $scheme = "tcp";
        private static $host = "192.168.0.102";
        private static $port = 6379;
        private static $predis = false;

        private static function getRedis(){
            if (!self::$predis){
                try {
                    require __DIR__."/predis/autoload.php";
                    self::$predis = new \Predis\Client([
                        'scheme' => self::$scheme,
                        'host'   => self::$host,
                        'port'   => self::$port,
                    ]);
                }
                catch (Exception $e) {
                    self::$predis = false;
                }

            }
            return self::$predis;
        }

        public static function get($key){
            $response = false;
            $redis = self::getRedis();
            if ($redis){
                if ($redis->exists($key)){
                    $response = unserialize($redis->get($key));
                }
            }

            return $response;
        }

        public static function set($key, $value){
            $response = false;
            $redis = self::getRedis();
            if ($redis){
                $redis->set($key, serialize($value));
                $response = true;
            }
            return $response;
        }

    }