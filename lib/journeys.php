<?php

function get_journey_info($operator, $service, $date, $time, $db)
{
	$ds = $date . " " . substr($time, 0, 2) . ":" . substr($time, 2, 2) . ":00";
	$dt = strtotime($ds);
	$id = "";
	$query = "select journeypattern,routeid from routeschedule where operator='" . $db->escape_string($operator) . "' and service='" . $db->escape_string($service) . "' and journeydate='" . $db->escape_string($ds) . "';";
	$res = $db->query($query);
	$ret = array();
	if($row = $res->fetch_assoc())
	{
		$id = $row['journeypattern'];
		$ret['route'] = $row['routeid'];
		$ret['start_time'] = $dt;
	}
	if(strlen($id) == 0) { return($ret); }

	$query = "select (SUM(runtime) + SUM(waittime)) as duration from journeyschedule where journeypattern='" . $db->escape_string($id) . "';";
	$res = $db->query($query);
	if($row = $res->fetch_assoc())
	{
		$ret['duration'] = (int) $row['duration'];
		$ret['end_time'] = $dt + $ret['duration'];
	}

	return($ret);
}

function get_journey_stops($operator, $service, $date, $time, $db)
{
	# TODO: Change this to use the schedule table.

	$ds = $date . " " . substr($time, 0, 2) . ":" . substr($time, 2, 2) . ":00";
	$dt = strtotime($ds);

	$id = $operator . "/" . $service . "/" . date("Y-m-d/Hi", $dt);
	$query = "select '' as time, date, atcocode, naptancode, commonname, indicator, bearing, latitude, longitude from stops, (select sequence, stop_id, date from schedule where journey='" . $id . "') as schedule where stops.atcocode=stop_id order by sequence ASC";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$dt = strtotime($row['date'] . " GMT");
		$row['time'] = date("H:i", $dt);
		$row['lat'] = (float) $row['latitude'];
		$row['lon'] = (float) $row['longitude'];
		unset($row['latitude']);
		unset($row['longitude']);
		$ret[] = $row;
	}

	return($ret);
}

