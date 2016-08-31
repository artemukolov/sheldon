<?php

    abstract class SheldonCache
    {
        private static $cacher = "redis";
        public static function getCache($key){
            $cacher = '\Sheldon\cacher\\'.SheldonCache::$cacher;
            if (class_exists($cacher)) {
                return $cacher::get($key);
            } else {
                return false;
            }
        }
        public static function setCache($key, $data){
            $cacher = '\Sheldon\cacher\\'.SheldonCache::$cacher;
            if (class_exists($cacher)) {
                return $cacher::set($key, $data);
            } else {
                return false;
            }
        }
    }