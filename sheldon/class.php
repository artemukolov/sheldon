<?php
	abstract class Sheldon {

	    public static function table($tableName) {

            $instance = new SheldonModel;

            $instance->table = $tableName;

            $instance->scheme = [];

            $instance->modelName = "";

            return $instance;

        }

	    public static function __callStatic($method, $parameters) {

    		$instance = new SheldonModel;

    		$instance->table = (isset(static::$tableName)? static::$tableName: mb_strtolower(get_called_class()));

    		$instance->scheme = (isset(static::$scheme)? static::$scheme: []);

			$instance->modelName = get_called_class();

    		return call_user_func_array(array($instance, $method), $parameters);

	    }
	}