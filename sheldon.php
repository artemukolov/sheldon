<?php
	//database settings
	require_once(__DIR__."/sheldon/db.php");
	//sheldon model
	require_once(__DIR__."/sheldon/model.php");
	//abstract class
	require_once(__DIR__."/sheldon/class.php");

	//query construct drivers
	$drivers = scandir(__DIR__."/sheldon/drivers");
	foreach ($drivers as $driver)
	{
		$file = __DIR__."/sheldon/drivers/".$driver;
		if (!is_dir($file)) require_once($file);

	}