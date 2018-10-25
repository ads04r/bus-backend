<?php

function get_place_info($fhrsid, $db)
{
	$query = "select * from fhrsplaces where id='" . $db->escape_string($fhrsid) . "';";
	$res = $db->query($query);
	$ret = array();
	if($row = $res->fetch_assoc())
	{
		$ret = $row;
		$pp = array();
		$pp['latitude'] = (float) $row['latitude'];
		$pp['longitude'] = (float) $row['longitude'];
		$ret['geo'] = $pp;
		unset($ret['latitude']);
		unset($ret['longitude']);
	}
	return($ret);
}

function get_place_buses($fhrsid, $db, $max=30)
{
	$query = "select journey as id, route, service as name, destination as dest, stop_id, date as ds, journey_number as j from schedule, (select stops.atcocode as stopid from placestops, stops where stopid=atcocode and fhrsid='" . $db->escape_string($fhrsid) . "') as x where stopid=stop_id and date>DATE_SUB(NOW(), INTERVAL 5 MINUTE) order by date ASC limit 0," . $db->escape_string($max) . ";";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$row['journey'] = (int) $row['j'];
		unset($row['j']);
		$row['date'] = strtotime($row['ds'] . " GMT");
		unset($row['ds']);
		$row['time'] = date("H:i", $row['date']);
		$ret[] = $row;
	}
	return($ret);
}

function get_place_stops($fhrsid, $db)
{
	$query = "select stops.*,distance from placestops, stops where stopid=atcocode and fhrsid='" . $db->escape_string($fhrsid) . "' order by distance ASC;";
	$res = $db->query($query);
	$ret = array();
	while($row = $res->fetch_assoc())
	{
		$item = array();
		$item['id'] = $row['atcocode'];
		$item['label'] = $row['commonname'];
		$item['indicator'] = $row['indicator'];
		$item['street'] = $row['street'];
		$item['lat'] = (float) $row['latitude'];
		$item['lon'] = (float) $row['longitude'];
		$item['distance'] = (int) $row['distance'];
		$ret[] = $item;
	}
	return($ret);
}

function get_places($db)
{
	$query = "select * from fhrsplaces order by label ASC;";
	$res = $db->query($query);
	$ret = array();
	while($row = $res->fetch_assoc())
	{
		$item = array();
		$item['id'] = $row['id'];
		$item['label'] = $row['label'];
		$item['lat'] = (float) $row['latitude'];
		$item['lon'] = (float) $row['longitude'];
		$item['address'] = $row['address'];
		$item['postcode'] = $row['postcode'];
		$ret[] = $item;
	}
	return($ret);
}

function index_place_stops($db)
{
	function stop_distance_sort($a, $b)
	{
		if($a['distance'] < $b['distance']) { return -1; }
		if($a['distance'] > $b['distance']) { return 1; }
		return 0;
	}

	$places = get_places($db);
	foreach($places as $place)
	{
		$query = "select * from stops where latitude>(" . $place['lat'] . " - 0.025) and latitude<(" . $place['lat'] . " + 0.025) and longitude>(" . $place['lon'] . " - 0.025) and longitude<(" . $place['lon'] . " + 0.025)";
		$res = $db->query($query);
		$stops = array();
		while($row = $res->fetch_assoc())
		{
			$row['distance'] = (int) (1000 * distance($place['lat'], $place['lon'], $row['latitude'], $row['longitude']));
			if($row['distance'] > 2000) { continue; }
			$stops[] = $row;
		}
		usort($stops, "stop_distance_sort");
		$stops = array_slice($stops, 0, 10);

		error_log($place['label'] . ", " . $place['address']);

		foreach($stops as $stop)
		{
			$query = "insert ignore into placestops (fhrsid, stopid, distance) values ('" . $place['id'] . "', '" . $stop['atcocode'] . "', '" . $stop['distance'] . "');";
			$db->query($query);
		}
	}
}

