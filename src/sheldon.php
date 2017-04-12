<?php

	namespace Sheldon;

	require_once(__DIR__ . "/db.php");
	require_once(__DIR__ . "/model.php");
	require_once(__DIR__ . "/class.php");
	require_once(__DIR__ . "/cacher.php");

	$drivers = scandir(__DIR__ . "/drivers");
	foreach ($drivers as $driver) {
		$file = __DIR__ . "/drivers/" .$driver;
		if (!is_dir($file)) {
			require_once($file);
		}
	}