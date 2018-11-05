<?php

date_default_timezone_set("Europe/London");

$etc_dir = dirname(dirname(__file__)) . "/etc";
$lib_dir = dirname(dirname(__file__)) . "/lib";
$var_dir = dirname(dirname(__file__)) . "/var";
$cfg = array();
if ($handle = opendir($lib_dir))
{
	while (false !== ($entry = readdir($handle)))
	{
		if(preg_match("/\\.php$/", $entry) == 0) { continue; }
		$lib = $lib_dir . "/" . $entry;
		include_once($lib);
	}
	closedir($handle);
}
if ($handle = opendir($etc_dir))
{
	while (false !== ($entry = readdir($handle)))
	{
		$m = array();
		if(preg_match("/^(.*)\\.json$/", $entry, $m) == 0) { continue; }
		$cfg[$m[1]] = json_decode(file_get_contents($etc_dir . "/" . $entry), true);
		if(strcmp($m[1], "database") != 0) { continue; }
		if(!(array_key_exists("current", $cfg['database']))) { continue; }

		$curdb = $cfg['database']['current'];
		if(count($argv) > 1)
		{
			if(array_key_exists($argv[1], $cfg['database']['databases']))
			{
				$curdb = $argv[1];
			}
		}
		if(!(array_key_exists($curdb, $cfg['database']['databases'])))
		{
			$curdba = array_keys($cfg['database']['databases']);
			$curdb = $curdba[0];
		}

		$realdbconfig = $cfg['database']['databases'][$curdb];
		$cfg['database'] = $realdbconfig;
	}
	closedir($handle);
}
