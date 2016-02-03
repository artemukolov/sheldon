<?php
	Class Sheldon {
		
		private static $table = "";
		protected static $pdo = false;
		protected static $driver = "mysql";
		protected static $db = array(
			'mysql' => array(
				'driver'    => 'mysql',
				'host'      => 'localhost',
				'db'  		=> 'test',
				'user'  	=> 'root',
				'pass'		=> '',
				'charset'   => 'utf8',
				'collation' => 'utf8_unicode_ci',
				'prefix'    => ''
			),
		);

		//подготовленные данные для запроса
		protected $preparedData = array(
			"fields" => array(),
			"aliases" => array(),
			"filters" => array(),
			"orders" => array(),
			"limit" => array(),
		);

		//подготовленные данные для запроса
		protected $byField = false;
		//Поля применяемые для выборки
		protected $fields = array();
		//Асевдонимы выбираемых полей
		protected $aliases = array();
		//фильтры налагаемые на запрос
		protected $filters = array();
		//Поля упорядочиваний для выборки
		protected $orders = array();
		//Лимит для выборки
		protected $limit = array();
		//Массив объединений
		protected $joins = array();
		//Массив поглощенных таблиц
		protected $absorbs = array();

		private function connect(){

			$rez = false;
			
			if (self::$driver == "mysql"){
				$connectStr =  "mysql:host=".(self::$db['mysql']['host']).";dbname=".(self::$db['mysql']['db']);
				$user = self::$db['mysql']['user'];
				$pass = self::$db['mysql']['pass'];
				$this->pdo = new PDO($connectStr, $user, $pass);
			}
			return $this->pdo;

		}


		protected function init() {
			if (!isset($this)){
				$n = get_called_class();
				$o = new $n;
				$table = (isset($o->tableName)? $o->tableName: strtolower($n));
				$o->table = $table;
			} else {
				$o = $this;
			}
			return $o;
		}


		public function by(){

			$o = self::init();
			if (trim(func_get_arg(0)) <> ""){
				$o->byField = func_get_arg(0);	
			}
			return $o;
			
		}

		public function byId(){
			
			$o = self::init();
			$o->byField = "id";	
			return $o;
			
		}

		public function order(){

			$o = self::init();
			if (func_num_args() == 1){
				array_push($o->orders, ["field"=>func_get_arg(0)]);
			} else {
				array_push($o->orders, ["field"=>func_get_arg(0), "order"=>func_get_arg(1)]);
			}
			return $o;

		}	

		public function limit(){

			$o = self::init();
			$o->limits = array();
			if (func_num_args() == 1){
				$o->limit = ["limit"=>func_get_arg(0)];
			} else {
				$o->limit = ["offset"=>func_get_arg(0), "limit"=>func_get_arg(1)];
			}
			return $o;

		}	

		public function select(){
			$o = self::init();
			$o->fields = array();

			if (func_num_args() == 1){
				$str = str_replace(",", "", func_get_arg(0));
				$str = str_replace(";", " ", $str);
				$arr = explode(" ", $str);
				foreach ($arr as $key => $value) {
					if (trim($value) <> ""){
						
						if (!(strpos($value, "@")) == false){
							$arr1 = explode("@", $value);
							array_push($o->fields, $arr1[0]);
							$o->aliases[$arr1[0]] = $arr1[1];
						} else {
							array_push($o->fields, $value);
						}
					}
				}
			} else {
				for ($i = 0; $i < func_num_args(); $i++) {
					$value = func_get_arg($i);
					if (trim($value) <> ""){
						array_push($o->fields, $value);
					}
			    }
			}
			return $o;
		}


		//Поглощение таблицы
		public function absorb(){
			$o = self::init();

			$outer_o = false;
			if (is_object(func_get_arg(0))){
				$outer_o = func_get_arg(0);
			} else {
				$outer_Class = func_get_arg(0);
				$outer_o = new $outer_Class;
				$table = (isset($outer_o->tableName)? $outer_o->tableName: strtolower($outer_Class));
				$outer_o->table = $table;
			}


			$inner_key = false;
			$outer_key = false;
			//TODO Вынести в функцию

			if (func_num_args() > 1){
				if (func_num_args() == 3){
					$inner_key = func_get_arg(1);
					$outer_key = func_get_arg(2);
				} 
			} else {
				if (isset($outer_o->scheme)){
					foreach ($outer_o->scheme as $field => $value) {
						if (isset($value['belongs'])){
							$arr = explode(" ", $value['belongs']);
							if (rtrim($arr[0]) == get_class($o)){
								if (count($arr)>1){
									if (count($arr) == 2){
											$inner_key = $arr[1];
											$outer_key = $field;
									} 
								} else {
									$inner_key = "id";
									$outer_key = $field;
								}
							}
						}
					}
				}
			}

			if (($inner_key <> false) and ($outer_key <> false)){

				$o->absorbs[$outer_o->table] = array(
					"key" => $inner_key,
					"data" => array()
				);
				$arr = $outer_o->get();

				foreach ($arr as $rec) {
					if (!(array_key_exists($rec[$outer_key], $o->absorbs[$outer_o->table]["data"]))){
						$o->absorbs[$outer_o->table]["data"][$rec[$outer_key]] = array();
					}
					array_push($o->absorbs[$outer_o->table]["data"][$rec[$outer_key]], $rec);
				}
			}
			// echo "<pre>";
			// print_r($arr);
			// echo "</pre>";

			return $o;			
		}

		//Соединение таблиц
		public function join(){

			$o = self::init();
			$outer_o = false;
			if (is_object(func_get_arg(0))){
				$outer_o = func_get_arg(0);
			} else {
				$outer_Class = func_get_arg(0);
				$outer_o = new $outer_Class;
				$table = (isset($outer_o->tableName)? $outer_o->tableName: strtolower($outer_Class));
				$outer_o->table = $table;
			}
			if (!(array_key_exists($outer_o->table, $o->joins))){
				$o->joins[$outer_o->table] = array(
					"fields" => $outer_o->fields,
					"aliases" => $outer_o->aliases,
					"comparisons" => array()
				);
			}
			// echo "<pre>";
			// print_r($outer_o);
			// print_r($outer_Class);	
			// echo "</pre>";
			if (func_num_args() > 1){
				if (func_num_args() == 3){
					array_push($o->joins[$outer_o->table]["comparisons"], array(
						func_get_arg(1), "=", func_get_arg(2)
					));
				} else {
					array_push($o->joins[$outer_o->table]["comparisons"], array(
						func_get_arg(1), func_get_arg(2), func_get_arg(3)
					));
				}
			} else {
				if (isset($outer_o->scheme)){
					foreach ($outer_o->scheme as $field => $value) {
						if (isset($value['belongs'])){
							$arr = explode(" ", $value['belongs']);
							if (rtrim($arr[0]) == get_class($o)){
								if (count($arr)>1){
									if (count($arr) == 2){
										array_push($o->joins[$outer_o->table]["comparisons"], array(
											$arr[1], "=", $field
										));
									} else {
										if (count($arr) == 3){
											array_push($o->joins[$outer_o->table]["comparisons"], array(
												$arr[2], $arr[1] , $field
											));
										}	
									}
								} else {
									array_push($o->joins[$outer_o->table]["comparisons"], array(
										"id", "=", $field
									));
								}
							}
						}
					}
				}
			}
		

			return $o;

		}

		public function where(){

			$o = self::init();

			if (func_num_args() == 3){
				array_push($o->filters, [
					"field" => func_get_arg(0),
					"operator" => func_get_arg(1),
					"value" => func_get_arg(2)
				]);
			} else {
				if (func_num_args() == 2){
					array_push($o->filters, [
						"field" => func_get_arg(0),
						"operator" => "=",
						"value" => func_get_arg(1)
					]);
				}	
			}
			
			return $o;
		}	
		protected function querySelect() {

			if (method_exists($this, "access_get")){
				$access = $this->access_get();
				foreach ($access as $key => $a) {
					

					if (count($a) == 2){
						$this->where($a[0], $a[1]);

					} else {
						if (count($a) == 2){
							$this->where($a[0], $a[1], $a[2]);
						}
					}
				}
			}
			$this->preparedData = [
				"fields" => $this->fields,
				"aliases" => $this->aliases,
				"filters" => $this->filters,
				"orders" => $this->orders,
				"limit" => $this->limit,
				"joins" => $this->joins
			];
			$q = $this->constructSelect($this->table, $this->preparedData);

			
			
			$resultArray = array();
			$result = $this->pdo->query($q);

			if ($result){
				$array = $result->fetchAll(PDO::FETCH_ASSOC);
				if (!($this->byField == false)){
					$array2 = array();
					foreach ($array as $key => $value) {
						$array2[$value[$this->byField]] = $value;
					}
					$resultArray = $array2;
				} else {
					$resultArray = $array;
				}
			}
			foreach ($resultArray as &$rec) {
				foreach ($this->absorbs as $nameOfAbsorb => $absorb) {
					$rec[$nameOfAbsorb] = (array_key_exists($rec[$absorb['key']], $absorb["data"]) ? $absorb["data"][$rec[$absorb['key']]] : array());
				}
			}	
			unset($rec);
			return $resultArray;
		}

		

		public function get(){

			$o = self::init();
			if ($o->connect()){

				return $o->querySelect();
			} else {
				return false;
			}

		}	

		public function all(){

			$o = self::init();
			if ($o->connect()){
				$o->filters = array();
				$o->limit = array();
				return $o->querySelect();
			} else {
				return false;
			}

		}	


		

		//QUERY BUILDERS
		private static function constructSelect($table, $data){

			// echo "<pre>";
			// print_r($data);
			// echo "</pre>";

	        $q = "SELECT ";
	        $fields = "";
	        if (isset($data["fields"])){
	        	if (count($data["fields"]) > 0){
	            	foreach ($data["fields"] as $f){
	            		$fields .= ($fields == ""? "":",").$table.".".$f.(!(array_key_exists($f, $data['aliases']) == false) ? " AS ".$data['aliases'][$f]:"");
	            	}
	        	} 
        	}
				
	        if (isset($data["joins"])) {
	        	foreach ($data["joins"] as $tableJoin=>$j) {
	        		
        			foreach ($j["fields"] as $f) {
        				
						$fields .= ($fields == ""? "":",").$tableJoin.".".$f.(!(array_key_exists($f, $j["aliases"]) == false) ? " AS ".$j['aliases'][$f]:"");

        			}
        		}
        	}


 			

	        if ($fields == ""){$fields = "*";}
	        $q = $q.$fields." FROM ".$table;
	        

	        $jns = "";
	        if (isset($data["joins"])) {
	        	
	        	foreach ($data["joins"] as $tableJoin=>$j) {
	        		$jn = "";
        			foreach ($j["comparisons"] as $c) {
    					$jn .= ($jn == ""? " JOIN ".$tableJoin. " ON " :" AND "). " " .$table.".".$c[0]." ".$c[1]." ".$tableJoin.".".$c[2];
    				}
    				$jns .= $jn;		
        				
	        	}
	        }	

	        $flt = "";
	        if (isset($data["filters"])) {
	            foreach ($data["filters"] as $f) {
	                if ($f['operator'] == 'in'){
	                    if (isset($f['value'])) {
	                        $arr = "";
	                        foreach ($f['value'] as $v) {
	                            $arr .= ($arr == '' ? '' : ', ') . "'" . $v . "'";
	                        }
	                        $flt .= ($flt == "" ? "" : " AND ") . "`" . $f['field'] . "` IN (" . $arr . ")";
	                    } else {
	                        return "";
	                    }
	                } elseif ($f['operator'] == 'notin'){
	                    if (isset($f['value'])) {
	                        $arr = "";
	                        foreach ($f['value'] as $v) {
	                            $arr .= ($arr == '' ? '' : ', ') . "'" . $v . "'";
	                        }
	                        $flt .= ($flt == "" ? "" : " AND ") . "`" . $f['field'] . "` NOT IN (" . $arr . ")";
	                    } else {
	                        return "";
	                    }
	                }
	                else {
	                    $flt .= ($flt == "" ? "" : " AND ") . "`" . $f['field'] . "` " . $f['operator'] . " '" . $f['value'] . "'";
	                }
	            }
	        }
	        $ord = "";
	        if (isset($data["orders"])){
	            foreach ($data["orders"] as $ordrec){
	                $ord = ($ord == "" ? "": ",")." ".$ordrec['field']." ".(isset($ordrec['order']) ? $ordrec['order'] : "");
	            }
	        }
	        $lim = "";
	        if (isset($data["limit"])){
	        	if (count($data["limit"]) > 0){
	            	$lim = (isset($data["limit"]['offset'])?$data["limit"]['offset'] : "0").", ".$data["limit"]['limit'];
	            }	
	        }
	        if ($jns<>""){$q .= $jns;}
	        if ($flt<>""){$q .= ' WHERE '.$flt;}
	        if ($ord<>""){$q .= ' ORDER BY '.$ord;}
	        if ($lim<>""){$q .= ' LIMIT '.$lim;}
	        //Здесь будет порядок
	        echo $q;
	        return $q;
	        

	    }
	}