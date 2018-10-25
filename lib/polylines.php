<?php

include_once($lib_dir . "/polyline-encoder/src/Polyline.php");

function get_route_polyline($route, $db)
{
	$points = array();
	$stops = get_route_stops($route, $db);
	$last = array();
	foreach($stops as $stop)
	{
		if(count($last) == 0) { $last = $stop; continue; }

		$polyline = get_polyline($last['id'], $stop['id'], $db);
		$points = array_merge($points, $polyline);

		$last = $stop;
	}

	return($points);
}

function get_polyline($from, $to, $db)
{
	global $cfg;

	$p = new Polyline();

	$query = "select * from stoppolylinelink where `from`='" . $db->escape_string($from) . "' and `to`='" . $db->escape_string($to) . "';";
	$res = $db->query($query);
	$ret = "";
	if($row = $res->fetch_assoc())
	{
		$ret = $row['polyline'];
	}
	$res->free();

	if(strlen($ret) > 0) { return($p->pair($p->decode($ret))); }

	if(!(array_key_exists("config", $cfg))) { return(array()); }
	if(!(array_key_exists("route-api", $cfg['config']))) { return(array()); }

	$query = "select * from journeypatterntiminglink where `from`='" . $db->escape_string($from) . "' and `to`='" . $db->escape_string($to) . "';";
	$res = $db->query($query);
	$r = $res->num_rows;
	$res->free();

	if($r == 0) { return(array()); }

	$f = get_stop_data($from, $db);
	$t = get_stop_data($to, $db);

	$url = str_replace("[from]", $f['latitude'] . "," . $f['longitude'], str_replace("[to]", $t['latitude'] . "," . $t['longitude'], $cfg['config']['route-api']));
	$data = json_decode(file_get_contents($url), true);

	sleep(rand(2, 6));

	if(!(is_array($data))) { return(array()); }
	if(!(array_key_exists("status", $data))) { return(array()); }
	if(strcmp($data['status'], "OK") != 0) { return(array()); }

	if(!(array_key_exists("routes", $data))) { return(array()); }
	$routes = $data['routes'];
	if(!(is_array($routes))) { return(array()); }
	if(count($routes) == 0) { return(array()); }
	$route = $routes[0];
	if(!(array_key_exists("overview_polyline", $route))) { return(array()); }
	$polyline = $route['overview_polyline'];
	if(!(array_key_exists("points", $polyline))) { return(array()); }

	$ret = $polyline['points'];
	if(strlen($ret) > 0)
	{
		$query = "insert ignore into stoppolylinelink (`from`, `to`, `polyline`) values ('" . $db->escape_string($from) . "', '" . $db->escape_string($to) . "', '" . $db->escape_string($ret) . "');";
		$db->query($query);
	}

	return($p->pair($p->decode($ret)));
}

