<?php

	namespace Sheldon;

	require_once(__DIR__ . "/db.php");
	require_once(__DIR__ . "/model.php");
	require_once(__DIR__ . "/class.php");
	require_once(__DIR__ . "/cacher.php");
	require_once(__DIR__ . "/paginator.php");

	$drivers = scandir(__DIR__ . "/drivers");
	foreach ($drivers as $driver)
	{
		$file = __DIR__ . "/drivers/" .$driver;
		if (!is_dir($file))
		{
			require_once($file);
		}

	}

	$cachers = scandir(__DIR__ . "/cachers");
	foreach ($cachers as $cacher)
	{
		$dir = __DIR__ . "/cachers/" .$cacher;
		if (is_dir($dir))
		{
			$cacher_file = __DIR__ . "/cachers/" .$cacher."/cacher.php";
			if (file_exists($cacher_file)){
				require_once($cacher_file);
			}
		}
	}
