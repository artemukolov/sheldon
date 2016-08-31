<?php
	abstract class Sheldon {

		public static $cachedFunctions = [];
		private static $sheldonModelPaths = [
			# __DIR__,
			# some dir to Sheldon Models
		];
	    public static function table($tableName){
            $instance = new SheldonModel;
            $instance->table = $tableName;
            $instance->scheme = [];
            $instance->modelName = "";
            return $instance;
        }

		public static function cacheFunction($arrayOfCachedFunctions){
			$class = get_called_class();
			if (!array_key_exists($class, Sheldon::$cachedFunctions)){
				Sheldon::$cachedFunctions[$class] = [];
			}
			Sheldon::$cachedFunctions[$class] = array_merge(Sheldon::$cachedFunctions[$class], $arrayOfCachedFunctions);
		}

		private static function getCashe($class, $method, $parameters){
			$response = -1;
			if (array_key_exists($class, Sheldon::$cachedFunctions)){
				if (array_key_exists($method, Sheldon::$cachedFunctions[$class])){
					$key = $class."^".$method;
					$keyA = $class."^".$method."//sheldonActualVersion";
					$keyC = $class."^".$method."//sheldonCurrentVersion";
					$a = SheldonCache::getCache($keyA);
					$c = SheldonCache::getCache($keyC);
					$i = 0;
					while ((!$a) or ($a <> $c)){
						usleep(100);
						$i += 1;
						$a = SheldonCache::getCache($keyA);
						$c = SheldonCache::getCache($keyC);
						if ($i>200){break;}
					}
					$response = SheldonCache::getCache($key);
					if (isset(Sheldon::$cachedFunctions[$class][$method]['handler'])){
						$handler = Sheldon::$cachedFunctions[$class][$method]['handler'];
						if (method_exists($class, $handler)){
							$response = $class::$handler($response, $parameters);
						}
					}

					if ((isset($parameters["sheldonPaginationOffsetKeys"])) && (isset($parameters["sheldonPaginationOffsetCount"]))){
						$response = SheldonPaginator::paginate($response, $parameters["sheldonPaginationOffsetKeys"], $parameters["sheldonPaginationOffsetCount"]);
					}
				}
			}
			return $response;
		}

		private static function requireSheldonModelPaths(){
			foreach (Sheldon::$sheldonModelPaths as $path){
				if (is_dir($path)){
					$list = scandir($path);
					foreach ($list as $path_in){
						require_once($path_in);
					}
				} else {
					require_once($path);
				}
			}
		}

		public static function updateCaches(){
			Sheldon::requireSheldonModelPaths();
			foreach (Sheldon::$cachedFunctions as $class=>$fns){
				foreach ($fns as $fn=>$fn_array){
					$alias_method = (isset($fn_array['alias']) ? $fn_array['alias']: $fn."_");
					if (method_exists($class, $alias_method)){
						$data = $class::$alias_method();
						$key = $class."^".$fn;
						$keyA = $class."^".$fn."//sheldonActualVersion";
						$keyC = $class."^".$fn."//sheldonCurrentVersion";
						$a = SheldonCache::getCache($keyA);
						$a = ($a? $a++: 1);
						if (isset($fn_array['indexField'])){
							$data_2 = [];
							foreach ($data as $row){
								$data_2[$row[$fn_array['indexField']]] = $row;
							}
							$data = $data_2;
						}
						SheldonCache::setCache($key, $data);
						SheldonCache::setCache($keyA, $a);
						SheldonCache::setCache($keyC, $a);
					}

				}
			}
		}

		private static function getPage($method, $parameters, $ids){

		}

		public static function __callStatic($method, $parameters) {

			$result = Sheldon::getCashe(get_called_class(), $method, $parameters[0]);
			if ($result <> -1){
				return $result;
			}
    		$instance = new SheldonModel;
    		$instance->table = (isset(static::$tableName)? static::$tableName: mb_strtolower(get_called_class()));
    		$instance->scheme = (isset(static::$scheme)? static::$scheme: []);
			$instance->modelName = get_called_class();
    		return call_user_func_array(array($instance, $method), $parameters);
	    }

		public static function error($message = "") {
			throw new Exception($message);
		}
	}