<?php

function get_stop_schedule($atco, $db)
{
	$dt = time();
	$query = "select journey as id, route, service as name, destination as dest, date as ds, journey_number as j from schedule where stop_id='" . $db->escape_string($atco) . "' and date>'" . gmdate("Y-m-d H:i:s", $dt - (5 * 60)) . "' order by date ASC limit 0,50;";
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

function get_stop_data($atco, $db)
{
	$query = "select * from stops where atcocode='" . $db->escape_string($atco) . "';";
	$ret = array();
	$res = $db->query($query);
	if($row = $res->fetch_assoc())
	{
		$ret = $row;
	}
	return($ret);
}

function get_stop_services($atco, $db)
{
	$query = "select distinct operator,service,routeid from routeschedule,(select distinct journeypattern from journeyschedule where `from`='" . $db->escape_string($atco) . "' or `to`='" . $db->escape_string($atco) . "') as x where x.journeypattern=routeschedule.journeypattern order by operator,service ASC;";
	$ret = array();
	$item = array();
	$rid = "";
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$id = $row['operator'] . '/' . $row['service'];
		if((strcmp($id, $rid) != 0) & (count($item) > 0))
		{
			$ret[] = $item;
			$item = array();
		}
		$item['service'] = $row['service'];
		$item['operator'] = $row['operator'];
		if(!(array_key_exists("routes", $item))) { $item['routes'] = array(); }
		$item['routes'][] = $row['routeid'];
		$rid = $id;
	}
	if(count($item) > 0) { $ret[] = $item; }

	return($ret);
}

function get_stop_places($atco, $db)
{
	$query = "select places.*,distance from placestops, places where placestops.fhrsid=places.fhrsid and stopid='" . $db->escape_string($atco) . "' order by distance ASC;";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$ret[] = $row;
	}
	return($ret);
}

function get_stops($stopfile)
{
	$f = file($stopfile);
	$headers = array();
	$data = array();
	foreach($f as $l)
	{
		$fields = str_getcsv($l);
		if(count($headers) == 0)
		{
			$headers = $fields;
			$c = count($headers);
			continue;
		}
		$i = 0;
		$item = array();
		for($i = 0; $i < $c; $i++)
		{
			$k = $headers[$i];
			while(count($fields) < $c)
			{
				$fields[] = "";
			}
			$item[$k] = utf8_encode($fields[$i]);
		}

		$data[] = $item;
	}

	return($data);
}

function import_stops($stopfile, $db, $maxlat=100, $minlat=-100, $maxlon=360, $minlon=-360)
{
	$headers = array();
	$columns = array('atcocode', 'naptancode', 'commonname', 'shortcommonname', 'landmark', 'street', 'crossing', 'indicator', 'bearing', 'nptglocalitycode', 'latitude', 'longitude', 'stoptype', 'busstoptype', 'timingstatus', 'defaultwaittime', 'status');
	$count = 0;

	$localities = array();

	if(!(($handle = fopen($stopfile, "r")) !== FALSE))
	{
		return 0;
	}
	while (($row = fgetcsv($handle, 1000, ",")) !== FALSE)
	{
		if(count($headers) == 0)
		{
			$headers = $row;
			continue;
		}
		$c = count($headers);
		$cx = count($row);
		if($cx < $c) { $c = $cx; }
		$item = array();
		$locid = "";
		$locname = "";
		$locparentname = "";
		$locparentparentname = "";
		for($i = 0; $i < $c; $i++)
		{
			$header = trim(strtolower($headers[$i]));
			$value = trim($row[$i]);
			if(strcmp($header, "nptglocalitycode") == 0) { $locid = $value; }
			if(strcmp($header, "localityname") == 0) { $locname = $value; }
			if(strcmp($header, "parentlocalityname") == 0) { $locparentname = $value; }
			if(strcmp($header, "grandparentlocalityname") == 0) { $locparentparentname = $value; }
			if(!(in_array($header, $columns))) { continue; }
			if(strlen($value) == 0) { continue; }
			$item[$db->escape_string($header)] = $db->escape_string($value);
		}

		if($item['latitude'] < $minlat) { continue; }
		if($item['latitude'] > $maxlat) { continue; }
		if($item['longitude'] < $minlon) { continue; }
		if($item['longitude'] > $maxlon) { continue; }

		$query = "insert ignore into stops (`" . implode("`, `", array_keys($item)) . "`) values ('" . implode("', '", array_values($item)) . "');";
		$db->query($query);
		$count = $count + ($db->affected_rows);
		$stops = array();

		if((strlen($locid) > 0) & (strlen($locname) > 0))
		{
			$localities[$locid] = "('" . $db->escape_string($locid) . "', '" . $db->escape_string($locname) . "', '" . $db->escape_string($locparentname) . "', '" . $db->escape_string($locparentparentname) . "')";
		}
	}

	if(count($localities) > 0)
	{
		$query = "insert ignore into localities (id, localityname, parentlocalityname, grandparentlocalityname) values " . implode(", ", array_values($localities));
		$db->query($query);
	}

	return($count);
}

function get_db_stops($mysqli)
{
	$query = "select atcocode from stops;";
	$res = $mysqli->query($query);
	$stops = array();
	while ($row = $res->fetch_array(MYSQLI_ASSOC))
	{
		$stops[] = $row['atcocode'];
	}
	return($stops);
}
