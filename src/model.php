<?php

	use Sheldon\driver;

	Class SheldonModel
	{

		public $table = "";
		protected $preparedData = array(
			"fields" => array(),
			"aliases" => array(),
			"filters" => array(),
			"orders" => array(),
			"limit" => array(),
		);
		protected static $absorbsMethods = [
			"absorb",
			"join",
			"leftJoin",
			"innerJoin",
			"rightJoin
		"];
		protected static $shortSelectModificators = [
			"c" => "COUNT",
			"d" => "DISTINCT",
			"mx" => "MAX",
			"mn" => "MIN"
		];
		private $pdo = false;
		protected $rawString = false;
		protected $gettingId = false;
		protected $byField = false;
		protected $combine = false;
		protected $fields  = [];
		protected $aliases = [];
		protected $modificators = [];
		protected $filters = [];
		protected $orders  = [];
		protected $limit   = [];
		protected $joins   = [];
		protected $absorbs = [];

	//core functions

		private function connect()
		{
			$this->pdo = SheldonDB::initPDO();
			return ($this->pdo? true: false);
		}

		private function queryAndFetch($query) {
			$data = $this->pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
			return $data;
		}

		private function query($query)
		{
			$data = $this->pdo->query($query);
			return $data;
		}



		private function init()
		{
			return $this;
		}

	// secondary functions

		protected function parseSelectItem($itemStr)
		{
			$field = $itemStr;
			$alias = "";
			$modificator = "";
			if (!(strpos($field, "@")) == false){
				$arr1 = explode("@", $field);
				$field = $arr1[0];
				$alias = $arr1[1];

			}
			if (!(strpos($field, "#")) == false){
				$arr2 = explode("#", $field);
				$field = $arr2[1];
				if (array_key_exists($arr2[0], self::$shortSelectModificators)){
					$modificator = self::$shortSelectModificators[$arr2[0]];
				} else {
					$modificator = mb_strtoupper($arr2[0]);
				}
			}
			if ($alias <> ""){
				$this->aliases[$field] = $alias;
			}
			if ($modificator <> ""){
				$this->modificators[$field] = $modificator;
			}
			return $field;
		}

		protected function addModel($args, $type, $mode = "")
		{
			$array = &$this->$type;
			$outer_o = false;
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

		protected function explodeWhereArray($args, $modificator = "AND")
		{
			$field = $args[0];
			if (count($args) == 3){
				$operator = $args[1];
				$value = $args[2];
			} elseif (count($args) == 2){
				$operator = "=";
				$value = $args[1];
			}
			if (is_array($field)){
				$field = $this->explodeWhereArray($field, $operator);
			}
			if (is_array($value)){
				$value = $this->explodeWhereArray($value, $operator);
			}
			return [
				"field" => $field,
				"operator" => $operator,
				"value" => $value,
				"modificator" => $modificator
			];
		}

		protected function addWhere($args, $modificator = "AND")
		{
			array_push($this->filters, $this->explodeWhereArray($args, $modificator));
		}

	//native functions

		public function select()
		{
			$this->fields = array();
			if (func_num_args() == 1){
				$str = str_replace(",", "", func_get_arg(0));
				$str = str_replace(";", " ", $str);
				$arr = explode(" ", $str);
				foreach ($arr as $key => $value) {
					if (trim($value) <> ""){
						array_push($this->fields, $this->parseSelectItem($value));
					}
				}
			} else {
				for ($i = 0; $i < func_num_args(); $i++) {
					$value = func_get_arg($i);
					if (trim($value) <> ""){
						array_pus($this->fields, $this->parseSelectItem($value));
					}
				}
			}
			return $this;
		}

		public function by()
		{
			if (trim(func_get_arg(0)) <> ""){
				$this->byField = func_get_arg(0);
			}
			return $this;
		}

		public function combineBy()
		{
			if (trim(func_get_arg(0)) <> ""){
				$this->byField = func_get_arg(0);
				$this->combine = true;
			}
			return $this;
		}

		public function raw()
		{
			if (trim(func_get_arg(0)) <> ""){
				$this->rawString = func_get_arg(0);
			}
			return $this;
		}


		public function order()
		{
			if (func_num_args() == 1){
				array_push($this->orders, ["field"=>func_get_arg(0)]);
			} else {
				array_push($this->orders, ["field"=>func_get_arg(0), "order"=>func_get_arg(1)]);
			}
			return $this;
		}

		public function limit()
		{
			$this->limits = [];
			if (func_num_args() == 1){
				$this->limit = ["limit"=>func_get_arg(0)];
			} else {
				$this->limit = ["offset"=>func_get_arg(0), "limit"=>func_get_arg(1)];
			}
			return $this;
		}

		public function join()
		{
			return self::addModel(func_get_args(), "joins");
		}

		public function innerJoin()
		{
			return self::addModel(func_get_args(), "joins", "INNER");
		}

		public function rightJoin()
		{
			return self::addModel(func_get_args(), "joins", "RIGHT");
		}

		public function leftJoin()
		{
			return self::addModel(func_get_args(), "joins", "LEFT");
		}

		public function absorb()
		{
			return self::addModel(func_get_args(), "absorbs");
		}

		public function where()
		{
			$this->addWhere(func_get_args());
			return $this;
		}

		public function whereOr()
		{
			$this->addWhere(func_get_args(), "OR");
			return $this;
		}

		public function whereRaw()
		{
			array_push($this->filters, [
				"raw" => func_get_arg(0)
			]);
			return $this;
		}

	//completion functions

		public function get()
		{
			if ($this->connect()){
				$result = $this->querySelect();
				return $result;
			} else {
				return false;
			}
		}

		public function delete()
		{
			if ($this->connect()){
				$result = $this->queryDelete();
				return $result;
			} else {
				return false;
			}
		}

		public function insert($data = [])
		{
			if ($this->connect()){
				return $this->queryInsert($data);
			} else {
				return false;
			}
		}

		public function insertGetId($data)
		{
			$this->gettingId = true;
			return $this->insert($data, $beforeSilense, $afterSilense);
		}

		public function update($data)
		{
			if ($this->connect()) {
				return $this->queryUpdate($data);
			} else {
				return false;
			}
		}

		public function all()
		{
			$this->filters = [];
			$this->limit = [];
			if ($this->connect()) {
				return $this->get();
			} else {
				return false;
			}
		}

	//prepare internal functions

		protected function querySelect()
		{
			$this->preparedData = [
				"fields"  	   => $this->fields,
				"aliases" 	   => $this->aliases,
				"modificators" => $this->modificators,
				"filters" 	   => $this->filters,
				"orders"  	   => $this->orders,
				"limit"   	   => $this->limit,
				"joins"   	   => $this->joins,
				"rawString"    => $this->rawString
			];
			$modelName = $this->modelName;
            if (method_exists($modelName, "beforeSelect")){
                $this->preparedData = $modelName::beforeSelect($this->preparedData);
            }
			$q = $this->constructSelect($this->table, $this->preparedData);
			$resultArray = array();
			$result = $this->queryAndFetch($q);
			if ($result){
				$array = $result;
				if (method_exists($modelName, "afterSelect")){
					$array = $modelName::afterSelect($array);
				}
				if (!($this->byField == false)){
					$array2 = array();
					foreach ($array as $key => $value) {
						if ($this->combine)
						{
							if (!(array_key_exists($value[$this->byField], $array2)))
							{
								$array2[$value[$this->byField]] = [];
							}
							array_push($array2[$value[$this->byField]], $value);
						}
						else
						{
							$array2[$value[$this->byField]] = $value;
						}
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

		protected function queryUpdate($data = [])
		{
            $this->preparedData = [
                "filters" => $this->filters
            ];
            $modelName = $this->modelName;
            if (method_exists($modelName, "beforeUpdate")){
                $this->preparedData = $modelName::beforeUpdate($data, $this->preparedData);
            }
            if ($data){
                $q = self::constructUpdate($this->table, $this->preparedData, $data);
                $res = $this->query($q);
            }
            return ($data);
        }

        protected function queryDelete($data = [])
		{
            $this->preparedData = [
                "filters" => $this->filters
            ];
		    $modelName = $this->preparedData;
            if (method_exists($modelName, "beforeDelete")){
                $this->preparedData = $modelName::beforeDelete($this->preparedData);
            }
            if (!($data === false)) {
                $q = self::constructDelete($this->table, $this->preparedData);
                $this->query($q);
            }
            return (true);
        }

		protected function queryInsert($data = [], $beforeSilense = false, $afterSilense = false)
		{
            $modelName = $this->modelName;
            if ((method_exists($modelName, "beforeInsert")) and (!($beforeSilense))){
             //   $data = $modelName::beforeInsert($data);
            }
			$q = self::constructInsert($this->table, $data);
            $result = $this->query($q);
			if ($result){
				if ($this->gettingId)
				{
					$id = $this->pdo->lastInsertId();
					$result = Sheldon::table($this->table)->where("id", $id)->get();
					if (count($result) > 0){
						//$modelName::afterInsert($result[0]);
					}
					return $id;
				}
				else
				{
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
			} else {
				return false;
			}
		}

		protected function constructSelect($table, $preparedData)
		{
			$driver = '\Sheldon\driver\\'.SheldonDB::$driver;
			if (class_exists($driver)) {
				return $driver::constructSelect($table, $preparedData);
			} else {
				return false;
			}
		}
		protected function constructInsert($table, $preparedData)
		{
			$driver = '\Sheldon\driver\\'.SheldonDB::$driver;
			if (class_exists($driver)) {
				return $driver::constructInsert($table, $preparedData);
			} else {
				return false;
			}
		}

	}

