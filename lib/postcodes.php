<?php

function get_postcodes($db)
{
        $query = "select * from postcodes order by postcode ASC;";
        $res = $db->query($query);
        $ret = array();
        while($row = $res->fetch_assoc())
        {
                $item = array();
                $item['postcode'] = $row['postcode'];
                $item['lat'] = (float) $row['latitude'];
                $item['lon'] = (float) $row['longitude'];
                $ret[] = $item;
        }
        return($ret);
}

function get_postcode($postcode, $db)
{
	$query = "select * from postcodes where postcode='" . preg_replace("/[^A-Z0-9]/", "", strtoupper($postcode)) . "';";
        $res = $db->query($query);
        $ret = array();
        if($row = $res->fetch_assoc())
        {
                $ret['postcode'] = $row['postcode'];
                $ret['lat'] = (float) $row['latitude'];
                $ret['lon'] = (float) $row['longitude'];
        }
        return($ret);
}

function get_postcode_stops($postcode, $db)
{
        $query = "select * from postcodestops,stops where stopid=atcocode and postcode='" . preg_replace("/[^A-Z0-9]/", "", strtoupper($postcode)) . "' order by distance ASC;";

        $res = $db->query($query);
        $ret = array();
        while($row = $res->fetch_assoc())
        {
		$item = array();
		$item['id'] = $row['stopid'];
		$item['label'] = $row['commonname'];
		$item['distance'] = (int) $row['distance'];
		$ret[] = $item;
        }

        return($ret);
}

function index_postcode_stops($db)
{
	function postcode_distance_sort($a, $b)
	{
		if($a['distance'] < $b['distance']) { return -1; }
		if($a['distance'] > $b['distance']) { return 1; }
		return 0;
	}

	$postcodes = get_postcodes($db);
	foreach($postcodes as $postcode)
	{
		$query = "select * from stops where naptancode<>'' and naptancode is not null and latitude>" . (((float) $postcode['lat']) - 0.1) . " and latitude<" . (((float) $postcode['lat']) + 0.1) . " and longitude>" . (((float) $postcode['lon']) - 0.1) . " and longitude<" . (((float) $postcode['lon']) + 0.1) . ";";
		$res = $db->query($query);
		$stops = array();
		while($row = $res->fetch_assoc())
		{
			$row['distance'] = (int) (1000 * distance($postcode['lat'], $postcode['lon'], $row['latitude'], $row['longitude']));
			if($row['distance'] > 2000) { continue; }
			$stops[] = $row;
		}
		usort($stops, "postcode_distance_sort");
		$stops = array_slice($stops, 0, 10);

		error_log($postcode['postcode'] . " - " . count($stops) . " stops");

		foreach($stops as $stop)
		{
			$query = "insert ignore into postcodestops (postcode, stopid, distance) values ('" . $postcode['postcode'] . "', '" . $stop['atcocode'] . "', '" . $stop['distance'] . "');";
			$db->query($query);
		}
	}
}

