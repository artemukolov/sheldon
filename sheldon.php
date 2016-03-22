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
				'pass'		=> 'agressor505',
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

		protected static $absorbsMethods = ["absorb", "join", "leftJoin", "innerJoin"];

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

				$connectStr =  "mysql:host=".(self::$db['mysql']['host']).";dbname=".(self::$db['mysql']['db'].";charset=UTF8");

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



			if (self::isAssoc($data)){

				$data_object = key($data);

                if (method_exists($data_object, "init")){

                    $object = $data_object::init();

                } else {

                    $object = $data_object;

                }



				foreach ($data[key($data)] as $value) {

					$key = key($value);



					if (strval($key) <> ""){


						$arr = $value[$key];

						foreach ($arr as &$rec) {
							
							if (self::isAssoc($rec)){

								if (in_array($key, self::$absorbsMethods) <> false){

										$rec = self::parse($rec);

								}

							}

						}

						unset($rec);



						$object = call_user_func_array([$object, $key], $arr);

					}
					
				}

			}



			return $object;

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

		protected function addModel($args, $type, $mode = ""){

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
					"object" => $outer_o,
					"mode" => $mode,
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

		public function innerJoin(){

			return self::addModel(func_get_args(), "joins", "INNER");

		}

		public function leftJoin(){

			return self::addModel(func_get_args(), "joins", "LEFT");

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

        public function whereRaw(){

            array_push($this->filters, [

                "raw" => func_get_arg(0)

            ]);

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


            $modelName = $this->modelName;

            if (method_exists($modelName, "beforeSelect")){

                $this->preparedData = $modelName::beforeSelect($this->preparedData);

            }

			$q = $this->constructSelect($this->table, $this->preparedData);



			$resultArray = array();

			$result = $this->pdo->query($q);



			if ($result){

				$array = $result->fetchAll(PDO::FETCH_ASSOC);
//                 echo "<pre>";
//                 print_r("ds --- ");
//                 print_r($result);
//                 print_r($array);
//                print_r(" --- ds");
//                 echo "</pre>";
                //var_dump($array);

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


				$absorbOject = $absorb['object'];

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

                    //TODO Это ограничение для того, чтобы при большых первичных таблицах не вешать запрос

                    $absorbOject = $absorbOject->where($comparison[2], "in", $fieldArray);

				}

				$absorbArray = $absorbOject->get();
				
				//print_r($absorbArray);
				//print_r($resultArray);
			    //print_r($absorb['comparisons']);

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


				}

			}

			unset($rec);



			return $resultArray;

		}

		
		protected function queryUpdate($data = []) {

            $this->preparedData = [

                "filters" => $this->filters

            ];

            $modelName = $this->modelName;

            if (method_exists($modelName, "beforeUpdate")){

                $this->preparedData = $modelName::beforeUpdate($data, $this->preparedData);

            }


            if ($data){

                $q = self::constructUpdate($this->table, $this->preparedData, $data);

                $res = $this->pdo->query($q);

            }

            return ($data);

        }

        protected function queryDelete($data = []) {

            $this->preparedData = [
                "filters" => $this->filters
            ];



            $modelName = $this->preparedData;

            if (method_exists($modelName, "beforeDelete")){

                $this->preparedData = $modelName::beforeDelete($this->preparedData);

            }


            if (!($data === false)) {

                $q = self::constructDelete($this->table, $this->preparedData);

                $this->pdo->query($q);
            }

            return (true);

        }

		protected function queryInsert($data = [], $beforeSilense = false, $afterSilense = false) {

            $modelName = $this->modelName;

            if ((method_exists($modelName, "beforeInsert")) and (!($beforeSilense))){
                $data = $modelName::beforeInsert($data);
            }


			$q = self::constructInsert($this->table, $data);

            $this->pdo->query($q);

            $result = Sheldon::table($this->table)->where("id", $this->pdo->lastInsertId())->get();

            if (count($result) > 0){
                $record = $result[0];
                if ((method_exists($modelName, "afterInsert")) and (!($afterSilense))) {
                    $record = $modelName::afterInsert($record);
                }
                return $record;
            } else {
                return false;
            }

		}



        protected function condition_where_exactlyIn_elements($key, $row, $field, $subfield, $value){

            $temp_arr = [];

            foreach ($row[$field] as $subRow){
                array_push($temp_arr,$subRow[$subfield]);
            }

            return (count(array_merge(array_diff($value, $temp_arr), array_diff($temp_arr, $value))) == 0);

        }

        protected function condition_where_inOrEmpty_elements($key, $row, $field, $subfield, $value){

            return (count($row[$field]) == 0? true: self::condition_where_in_elements($key, $row, $field, $subfield, $value));

        }

        protected function condition_where_in_elements($key, $row, $field, $subfield, $value){


            $forward = false;

            foreach ($row[$field] as $subRow){
                if (in_array($subRow[$subfield], $value) <> false){$forward = true;}
                if ($forward){break;}
            }

            return $forward;

        }

        protected function postCondition($innerData, $filters){
//



            foreach ($filters as $a){

                if (!is_array($a)){continue;}

                $preData = $innerData;

                $method = "condition_".$a[0]."_".$a[1]."_".str_replace("_", "", $a[3]);

//                var_dump($method);

                $preData = [];



                foreach ($innerData as $key=>$row){

                    $forward = (method_exists($this, $method)? $this->$method($key, $row, $a[2], $a[4], $a[5]): false);

                    if ($forward){
                        $preData[$key] = $row;
                    }
                }

                $innerData = $preData;


            }

            return $innerData;

        }

		public function get(){

			if ($this->connect()){

                $result = $this->querySelect();

                $filters = func_get_args();

                if (count($filters)>0){
                    $result = $this->postCondition($result, $filters);
                }

                return $result;

			} else {

				return false;

			}

		}

        public function delete(){



			if ($this->connect()){

                $result = $this->queryDelete();

                return $result;

			} else {

				return false;

			}

		}

		public function insert($data, $beforeSilense = false, $afterSilense = false){

			if ($this->connect()){

				return $this->queryInsert($data, $beforeSilense, $afterSilense);

			} else {

				return false;

			}

		}

		public function update($data){

			if ($this->connect()){

				return $this->queryUpdate($data);

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


        private static function parseFilters($table, $filters, $flt){


            foreach ($filters as $f) {

                if (isset($f['raw'])){

                    $flt .= ($flt == "" ? "" : " AND ").$f['raw'];

                } else {

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
                        $flt .= ($flt == "" ? "" : " AND ") . "" .$table.".". $f['field'] . " " . $f['operator'] . " " . (trim($f['value']) == ""? "": "'".$f['value']."'") ;
                    }
                }
            }

            return $flt;

        }


        private static function getWhereRow($table, $data){


            $flt = "";

            if (isset($data["filters"])) {

                $flt = self::parseFilters($table, $data["filters"], $flt);

            }

            if (isset($data["joins"])){

                foreach ($data["joins"] as $jTable => $j){

                    if (is_object($j['object'])){

                        if (isset($j['object']->filters)) {

                            $flt = self::parseFilters($jTable, $j['object']->filters, $flt);

                        }

                    }

                }
            }

            $flt = ($flt == ""? "": " WHERE ".$flt);

            return $flt;

        }
        private static function constructInsert($table, $data){

            $keys = "";
            $values = "";


            foreach ($data as $keyN=>$valueN){
                $keys .= ($keys == ""? "":",")."`".$keyN."`";
                $values .= ($values == ""? "":",")."'".$valueN."'";
            }
            $query = "INSERT INTO `".$table."` (".$keys.") VALUES (".$values.")";

	        return $query;
	    }

        private static function constructUpdate($table, $data, $changeData){

            $changedRow = "";
            foreach ($changeData as $keyN=>$valueN){

                $changedRow .=($changedRow==""? "":",")."`".$keyN."`='".$valueN."'";

            }

            $flt = self::getWhereRow($table, $data);

            $query = "UPDATE `" . $table . "` SET " . $changedRow . $flt;
//            var_dump($query);
            return $query;

        }


        private static function constructDelete($table, $data){

            $q = "DELETE FROM ";

            $q .= $table;

            $q .= self::getWhereRow($table, $data);

            return $q;

        }


		private static function constructSelect($table, $data){



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

                    //tst
	        		$jn = "";
        			foreach ($j["comparisons"] as $c) {
    					$jn .= ($jn == ""? " ".$j["mode"]." JOIN ".$tableJoin. " ON " :" AND "). " " .$table.".".$c[0]." ".$c[1]." ".$tableJoin.".".$c[2];
    				}
//                    echo "jj ".$jn;
    				$jns .= $jn;		
        				
	        	}
	        }	

	        $flt = self::getWhereRow($table, $data);

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
	        $q .= $flt;
	        if ($ord<>""){$q .= ' ORDER BY '.$ord;}
	        if ($lim<>""){$q .= ' LIMIT '.$lim;}

//	        echo "<pre>";
       //   print_r($q);
//	        echo "</pre>";

	        return $q;
	    }
	}

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
