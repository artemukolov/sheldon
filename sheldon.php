<?php
	Class SheldonModel {
		
		public $table = "";
		protected $pdo = false;
		protected static $driver = "mysql";
		protected static $db = array(
			'mysql' => array(
				'driver'    => 'mysql',
				'host'      => 'localhost',
				'db'  		=> 'to',
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
		protected $fields  = array();
		//Асевдонимы выбираемых полей
		protected $aliases = array();
		//фильтры налагаемые на запрос
		protected $filters = array();
		//Поля упорядочиваний для выборки
		protected $orders  = array();
		//Лимит для выборки
		protected $limit   = array();
		//Массив объединений
		protected $joins   = array();
		//Массив поглощенных таблиц
		protected $absorbs = array();

		public function isAssoc($arr){

			if (gettype($arr)<>"array") {return false;}

			if (strval($arr[key($arr)]=="")){

				return false;

			} else {

		    	return array_keys($arr) !== range(0, count($arr) - 1);

		    }

		}


		private function connect(){

			if (self::$driver == "mysql"){

				$connectStr =  "mysql:host=".(self::$db['mysql']['host']).";dbname=".(self::$db['mysql']['db']);

				$user = self::$db['mysql']['user'];

				$pass = self::$db['mysql']['pass'];

				$this->pdo = new PDO($connectStr, $user, $pass);

			}

			return $this->pdo;

		}



		public function by(){

			if (trim(func_get_arg(0)) <> ""){

				$this->byField = func_get_arg(0);	

			}

			return $this;
			
		}

		public function parse($data){

			$object = false;

//			var_dump(self::isAssoc($data));

			if (self::isAssoc($data)){

				$data_object = key($data);

				//echo "class - ".$data_object;

				$object = $data_object::init();

				foreach ($data[key($data)] as $value) {

					$key = key($value);

					if (strval($key) <> ""){
	
						

						$arr = $value[$key];

						foreach ($arr as &$rec) {
							
							// echo "<pre>";
							// print_r(self::isAssoc($rec));
							// echo "</pre>";

							if (self::isAssoc($rec)){
								
								$rec = self::parse($rec);

							}

						}

						unset($rec);
						// print_r("<br>METHOD: ");
						// print_r($key);
						// print_r("<br>OBJECT: ");
						// print_r($object->table);
						// if ($key=="get"){
						// 	print_r("GETTING W!!");
						// 	print_r($object);
						// }
						// print_r(" ");

						$object = call_user_func_array([$object, $key], $arr);

					}
					
				}

			}
				// echo "<pre>READY OBJECT";
	   //          print_r($object->table);
	   //          echo "</pre>";


			return $object;

          //   if (isset($data["filters"])){
          //       foreach ($data["filters"] as $f){
          //           if (count($f) == 3){
          //               array_push($this->filters, [
          //                   "field" => $f[0],
          //                   "operator" => $f[1],
          //                   "value" => $f[2]
          //               ]);
          //           } else {
          //               if (count($f) == 2){
          //                   array_push($this->filters, [
          //                       "field" => $f[0],
          //                       "operator" => "=",
          //                       "value" => $f[1]
          //                   ]);
          //               }
          //           }
          //       }
          //   }


          //   if (isset($data["absorbs"])){
          //       foreach ($data["absorbs"] as $a){
          //   		$this->addModel($a, "absorbs");
          //       }
          //   }

          //   if (isset($data["by"])){
        		// $this->by($data["by"]);
          //   }

          //   return $this->get();

        }

		public function order(){

			if (func_num_args() == 1){

				array_push($this->orders, ["field"=>func_get_arg(0)]);

			} else {

				array_push($this->orders, ["field"=>func_get_arg(0), "order"=>func_get_arg(1)]);

			}

			return $this;

		}	

		public function limit(){

			$this->limits = [];

			if (func_num_args() == 1){

				$this->limit = ["limit"=>func_get_arg(0)];

			} else {

				$this->limit = ["offset"=>func_get_arg(0), "limit"=>func_get_arg(1)];

			}

			return $this;

		}	

		public function select(){

			$this->fields = array();

			if (func_num_args() == 1){
				$str = str_replace(",", "", func_get_arg(0));
				$str = str_replace(";", " ", $str);
				$arr = explode(" ", $str);
				foreach ($arr as $key => $value) {
					if (trim($value) <> ""){
						if (!(strpos($value, "@")) == false){
							$arr1 = explode("@", $value);
							array_push($this->fields, $arr1[0]);
							$this->aliases[$arr1[0]] = $arr1[1];
						} else {
							array_push($this->fields, $value);
						}
					}
				}
			} else {
				for ($i = 0; $i < func_num_args(); $i++) {
					$value = func_get_arg($i);
					if (trim($value) <> ""){
						array_pus($this->fields, $value);
					}
			    }
			}

			return $this;

		}

		public function init(){

			return $this;

		}	

		protected function addModel($args, $type){

			$array = &$this->$type;

			$outer_o = false;

			//print_r($args);

			if (is_object($args[0])){
				$outer_o = $args[0];
			} else {
				$outer_Class = $args[0];
				$outer_o = $outer_Class::init();
			}

			

			if (!(array_key_exists($outer_o->table, $array))){
				$array[$outer_o->table] = array(
					"model" => $outer_o->modelName,
					"fields" => $outer_o->fields,
					"aliases" => $outer_o->aliases,
					"comparisons" => array()
				);
			}

			if (count($args) > 1){
				if (count($args) == 3){
					array_push($array[$outer_o->table]["comparisons"], array(
						$args[1], "=", $args[2]
					));
				} else {
					array_push($array[$outer_o->table]["comparisons"], array(
						$args[1], $args[2], $args[3]
					));
				}
			} else {
				
				if (isset($outer_o->scheme)){
						
					foreach ($outer_o->scheme as $field => $value) {

						foreach ($value as $mass) {

							$arr = explode("@", $mass[0]);

							if (rtrim($arr[0]) == ($this->modelName)){

								if (count($arr)>1){
									if (count($arr) == 2){
										array_push($array[$outer_o->table]["comparisons"], array(
											$arr[1], "=", $field
										));
									} else {
										if (count($arr) == 3){
											array_push($array[$outer_o->table]["comparisons"], array(
												$arr[2], $arr[1] , $field
											));
										}	
									}
								} else {
									array_push($array[$outer_o->table]["comparisons"], array(
										"id", "=", $field
									));
								}
							}
						}
					}
				}
			}
		

			return $this;

		}

		//Соединение таблиц
		public function join(){

			return self::addModel(func_get_args(), "joins");

		}

		//Поглощение таблицы
		public function absorb(){

			return self::addModel(func_get_args(), "absorbs");
					
		}


		public function where(){

        	if (func_num_args() == 3){

				array_push($this->filters, [

					"field" => func_get_arg(0),
					"operator" => func_get_arg(1),
					"value" => func_get_arg(2)

				]);

			} else {

				if (func_num_args() == 2){

					array_push($this->filters, [

						"field" => func_get_arg(0),
						"operator" => "=",
						"value" => func_get_arg(1)

					]);

				}	

			}
			
			return $this;

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
				"fields"  => $this->fields,
				"aliases" => $this->aliases,
				"filters" => $this->filters,
				"orders"  => $this->orders,
				"limit"   => $this->limit,
				"joins"   => $this->joins
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
			
			foreach ($this->absorbs as $table => $absorb) {

				$absorbOject = $absorb['model']::init();

				foreach ($absorb['comparisons'] as $comparison) {

					$fieldArray = [];

					if (array_key_exists($comparison[0], $this->aliases)){

						$realField = $this->aliases[$comparison[0]];

					} else {

						$realField = $comparison[0];

					}
					
					foreach ($resultArray as $value) {

						array_push($fieldArray, $value[$realField]);

					}

					$absorbOject = $absorbOject->where($comparison[2], "in", $fieldArray);
				}

				
				$absorbArray = $absorbOject->get();
				
				//print_r($absorbArray);
				// print_r($resultArray);
			//	print_r($absorb['comparisons']);

				foreach ($resultArray as &$rec) {
	
					$tempArray = $absorbArray;

					foreach ($absorb['comparisons'] as $comparison) {

						if (array_key_exists($comparison[0], $this->aliases)){

							$realField = $this->aliases[$comparison[0]];

						} else {

							$realField = $comparison[0];

						}


						$values = ["iternalValue" => $rec[$realField], "outerField"=>$comparison[2]];

						$tempArray = array_filter($tempArray, function($var)  use ($values)
			            	{return($var[$values['outerField']] == $values['iternalValue']);}
			        	);

						
					}

					$rec[$table] = $tempArray;



					// $abs = array_filter($absorbArray, function($var)  use ($goodsWithProviders)
			  //           {return(in_array(rtrim($var["code"]), $goodsWithProviders) == False);}
			  //       );

				}

			//	print_r($resultArray);

			}

			

//            foreach ($this->absorbs as $nameOfAbsorb => $absorb) {
//                var_dump($absorb);
//            }

			// foreach ($resultArray as &$rec) {
			// 	foreach ($this->absorbs as $nameOfAbsorb => $absorb) {

			// 		$rec[$nameOfAbsorb] = (array_key_exists($rec[$absorb['key']], $absorb["data"]) ? $absorb["data"][$rec[$absorb['key']]] : array());
			// 	}
			// }

			unset($rec);
			return $resultArray;
		}

		

		public function get($empty=false){

			//print_r("GET this object".$this->table);

			if ($this->connect()){
             //   var_dump($o);
                return $this->querySelect();
			} else {
				return false;
			}

		}	

		public function all(){

			$this->filters = [];

			$this->limit = [];

			if ($this->connect()){

				return $this->get();

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
			//echo "jj ".$jns;
	        $flt = "";
	        if (isset($data["filters"])) {
	            foreach ($data["filters"] as $f) {
	                if ($f['operator'] == 'in'){
	                    if (isset($f['value'])) {
	                        $arr = "";
	                        foreach ($f['value'] as $v) {
	                            $arr .= ($arr == '' ? '' : ', ') . "'" . $v . "'";
	                        }
	                        $flt .= ($flt == "" ? "" : " AND ") . "" . $table.".".$f['field'] . " IN (" . $arr . ")";
	                    } else {
	                        return "";
	                    }
	                } elseif ($f['operator'] == 'notin'){
	                    if (isset($f['value'])) {
	                        $arr = "";
	                        foreach ($f['value'] as $v) {
	                            $arr .= ($arr == '' ? '' : ', ') . "'" . $v . "'";
	                        }
	                        $flt .= ($flt == "" ? "" : " AND ") . "" .$table.".". $f['field'] . " NOT IN (" . $arr . ")";
	                    } else {
	                        return "";
	                    }
	                }
	                else {
	                    $flt .= ($flt == "" ? "" : " AND ") . "" .$table.".". $f['field'] . " " . $f['operator'] . " '" . $f['value'] . "'";
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
	        //var_dump($q);
	        return $q;
	    }
	}

	abstract class Sheldon {

	    public static function __callStatic($method, $parameters) {

    		$instance = new SheldonModel;

    		$instance->table = (isset(static::$tableName)? static::$tableName: mb_strtolower(get_called_class()));

    		$instance->scheme = (isset(static::$scheme)? static::$scheme: []);

			$instance->modelName = get_called_class();

    		return call_user_func_array(array($instance, $method), $parameters);

	    }
	}
