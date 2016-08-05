<?php
	Class SheldonDB 
	{
		protected static $db = array(
			'mysql' => array(
				'driver'    => 'mysql',
				'host'      => '',
				'db'  		=> 'test',
				'user'  	=> 'root',
				'pass'		=> '',
				'charset'   => 'utf8',
				'collation' => 'utf8_unicode_ci',
				'prefix'    => ''
			),
		);

		public static $driver = "mysql";

		public static function initPDO()
		{
			$pdo = false;

			if (self::$driver == "mysql"){


				$connectStr =  "mysql:host=".(self::$db["mysql"]['host']).";dbname=".(self::$db["mysql"]['db'].";charset=UTF8");

				$user = self::$db["mysql"]['user'];

				$pass = self::$db["mysql"]['pass'];

				$pdo = new PDO($connectStr, $user, $pass);

			}
			return $pdo;
		}
	}