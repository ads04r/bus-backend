<?php

function get_area_stops($id)
{
	$path = dirname(dirname(__file__)) . "/etc/areas/" . $id . ".json";
	if(!(file_exists($path))) { return(array()); }

	$info = json_decode(file_get_contents($path), true);
	return($info['stops']);
}

function get_area_info($id)
{
	$path = dirname(dirname(__file__)) . "/etc/areas/" . $id . ".json";
	if(!(file_exists($path))) { return(array()); }

	$ret = array();
	$info = json_decode(file_get_contents($path), true);
	$ret['id'] = $id;
	$ret['uri'] = $info['uri'];
	$ret['label'] = $info['label'];
	$ret['stops'] = count($info['stops']);
	return($ret);
}

function get_area_buses($id, $db, $max=30)
{
	function area_bus_sort($a, $b)
	{
		$ida = preg_replace("|(.+)/(.+)/([0-9\\-]+)/([0-9]{4})|", "\\3/\\4", $a['id']);
		$idb = preg_replace("|(.+)/(.+)/([0-9\\-]+)/([0-9]{4})|", "\\3/\\4", $b['id']);
		$r = strcmp($ida, $idb);
		if($r != 0) { return($r); }
		if($a['date'] > $b['date']) { return 1; }
		if($a['date'] < $b['date']) { return -1; }
		return 0;
	}

	$path = dirname(dirname(__file__)) . "/etc/areas/" . $id . ".json";
	if(!(file_exists($path))) { return(array()); }

	$info = json_decode(file_get_contents($path), true);
	$ret = array();
	foreach($info['stops'] as $stop)
	{
		$sch = get_stop_schedule($stop, $db);
		foreach($sch as &$st)
		{
			$st['stop'] = $stop;
		}
		$ret = array_merge($ret, $sch);
	}
	usort($ret, "area_bus_sort");
	$ret = array_slice($ret, 0, $max);
	return($ret);
}
