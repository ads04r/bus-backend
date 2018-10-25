<?php

function get_nearest_locality($lat, $lon, $db)
{
	$ret = array();
	$stops = get_nearest_stops($lat, $lon, $db, 1);
	if(count($stops) == 0) { return($ret); }
	$id = $stops[0]['id'];
	$query = "select localities.* from stops,localities where atcocode='" . $db->escape_string($id) . "' and localities.id=nptglocalitycode;";
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$ret = $row;
	}
	return($ret);
}

function get_nearest_stops($lat, $lon, $db, $max=10)
{
	$stops = array();
	$query = "select * from stops where latitude>" . ($lat - 0.1) . " and latitude<" . ($lat + 0.1) . " and longitude>" . ($lon - 0.1) . " and longitude<" . ($lon + 0.1) . ";";
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$row['dist'] = distance($lat, $lon, $row['latitude'], $row['longitude']);
		$stops[] = $row;
	}
	usort($stops, function($a, $b)
	{
		if($a['dist'] < $b['dist']) { return -1; }
		if($a['dist'] > $b['dist']) { return 1; }
		return 0;
	});
	$ret = array();
	foreach(array_slice($stops, 0, $max) as $stop)
	{
		$item = array();
		$item['id'] = $stop['atcocode'];
		$item['label'] = $stop['commonname'];
		$item['latitude'] = (float) $stop['latitude'];
		$item['longitude'] = (float) $stop['longitude'];
		$ret[] = $item;
	}

	return($ret);
}
