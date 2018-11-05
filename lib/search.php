<?php

function jaccard_index($a, $b)
{
	$inter = array_merge($a, $b);
	$union = array_unique($inter);
	$u_size = (float) count($union);
	$i_size = (float) count($inter) - $u_size;

	if($u_size == 0) { return(1.0); }
	return($i_size / $u_size);
}

function resolve_stops($search, $searchtype, $db)
{
	if(strlen($searchtype) == 0)
	{
		return(text_search($search, $db));
	}

	if(strcmp($searchtype, "latlon") == 0)
	{
		$ll = split(",", $search);
		$lat = (float) $ll[0];
		$lon = (float) $ll[1];
		$ret = array();
		foreach(get_nearest_stops($lat, $lon, $db) as $item)
		{
			$ret[] = $item['id'];
		}
		return(array(array("query" => $search, "type" => "latlon", "result" => $ret)));
	}

	if(strcmp($searchtype, "fhrs") == 0)
	{
		$ret = array();
		foreach(get_place_stops($search, $db) as $item)
		{
			$ret[] = $item['id'];
		}
		return(array(array("query" => $search, "type" => "fhrs", "result" => $ret)));
	}

	if(strcmp($searchtype, "locality") == 0)
	{
		$ret = array();
		foreach(get_locality_stops($search, $db) as $item)
		{
			$ret[] = $item['id'];
		}
		return(array(array("query" => $search, "type" => "locality", "result" => $ret)));
	}

	if(strcmp($searchtype, "stop") == 0)
	{
		$ret = array();
		$data = get_stop_data($search, $db);
		foreach(get_nearest_stops($data['latitude'], $data['longitude'], $db) as $item)
		{
			$ret[] = $item['id'];
		}
		return(array(array("query" => $search, "type" => "stop", "result" => $ret)));
	}

	if(strcmp($searchtype, "postcode") == 0)
	{
		$ret = array();
		foreach(get_postcode_stops(preg_replace("/[^0-9A-Z]/", "", strtoupper($search)), $db) as $item)
		{
			$ret[] = $item['id'];
		}
		return(array(array("query" => $search, "type" => "postcode", "result" => $ret)));
	}

	if(strcmp($searchtype, "uri") == 0)
	{
		return(array(array("query" => $search, "type" => "uri", "result" => search_uri_stops($search, $db))));
	}

	return(array());
}

function get_address($lat, $lon, $db)
{
	foreach(get_nearest_stops($lat, $lon, $db) as $stop)
	{
		$query = "select street,localityname,parentlocalityname from stops, localities where stops.nptglocalitycode=localities.id and atcocode='" . $db->escape_string($stop['id']) . "';";
		$res = $db->query($query);
		if($row = $res->fetch_assoc())
		{
			$data = implode("\t", array($row['street'], $row['localityname'], $row['parentlocalityname']));
			return(explode("\t", trim($data)));
		}
	}
	return("");
}

function text_search($search, $db)
{
	$ret = array();
	if(preg_match("/^([a-zA-Z0-9 ]+)$/", $search) > 0)
	{
		$item = resolve_stops($search, "postcode", $db);
		if(count($item) > 0) {
			if(count($item[0]['result']) > 0)
			{
				$ret = array_merge($ret, $item);
			}
		}
	}
	if(preg_match("/^([0-9\\.\\-]+),([0-9\\.\\-]+)$/", str_replace(" ", "", $search)) > 0)
	{
		$item = resolve_stops($search, "latlon", $db);
		if(count($item['result']) > 0) { $ret = array_merge($ret, array($item)); }
	}
	if(count($ret) == 0)
	{
		$item = resolve_stops($search, "uri", $db);
		if(count($item['result']) > 0) { $ret = array_merge($ret, array($item)); }
	}

	if(count($ret) > 0)
	{
		return($ret);
	}

	$places = find_places($search, $db);

	usort($places, function($a, $b)
	{
		if($a['diff'] < $b['diff']) { return -1; }
		if($a['diff'] > $b['diff']) { return 1; }
		return 0;
	});
	foreach($places as $place)
	{
		if(strcmp($place['type'], "street") == 0)
		{
			$ret[] = $place;
			continue;
		}
		$data_c = resolve_stops($place['id'], $place['type'], $db);
		if(count($data_c) == 0) { continue; }
		$data = $data_c[0];
		if(count($data['result']) == 0) { continue; }
		$data['label'] = $place['label'];
		$ret[] = $data;
	}
	if(count($ret) < 2) { return($ret); }
	if(strcmp($ret[0]['type'], "stop") != 0) { return($ret); }

	while(true)
	{
		$c = count($ret);
		if($c < 2) { break; }
		for($i = 0; $i < ($c - 1); $i++)
		{
			if(jaccard_index($ret[$i]['result'], $ret[($i + 1)]['result']) > 0)
			{
				$item = $ret[$i];
				$prev = array();
				if($i > 0)
				{
					$prev = array_slice($ret, 0, ($i - 1));
				}
				$item['type'] = "stop-area";
				$item['result'] = array_values(array_unique(array_merge($item['result'], $ret[($i + 1)]['result'])));
				$ret = array_merge(array($item), array_slice($ret, 2));
				if(count($prev) > 0)
				{
					$ret = array_merge($prev, $ret);
				}
				continue(2);
			}
		}
		break;
	}

	return($ret);
}

function find_places($search_string, $db)
{
	$ret = array();

	$query = "select id,localityname as label from localities where localityname like '" . $db->escape_string($search_string) . "' or localityname like 'the " . $db->escape_string($search_string) . "%';";
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$row['diff'] = levenshtein(strtolower($row['label']), strtolower($search_string));
		$row['type'] = "locality";
		$ret[] = $row;
	}
	if(count($ret) > 0) { return($ret); }

	$query = "select id,label from fhrsplaces where label like '" . $db->escape_string($search_string) . "%' or label like 'the " . $db->escape_string($search_string) . "%';";
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$row['diff'] = levenshtein(strtolower($row['label']), strtolower($search_string));
		$row['type'] = "fhrs";
		$ret[] = $row;
	}
	if(count($ret) > 0) { return($ret); }

	$query = "select id,label from fhrsplaces where label like '%" . $db->escape_string($search_string) . "%';";
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$row['diff'] = levenshtein($row['label'], $search_string);
		$row['type'] = "fhrs";
		$ret[] = $row;
	}
	if(count($ret) > 0) { return($ret); }

	$query = "select id,label from fhrsplaces where label like '%" . implode("%' and label like '%", explode(" ", $db->escape_string($search_string))) . "%';";
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$row['diff'] = levenshtein($row['label'], $search_string);
		$row['type'] = "fhrs";
		$ret[] = $row;
	}
	if(count($ret) > 0) { return($ret); }

	$query = "select atcocode as id,commonname as label from stops where commonname like '" . $db->escape_string($search_string) . "%' or commonname like 'the " . $db->escape_string($search_string) . "%';";
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$row['diff'] = levenshtein(strtolower($row['label']), strtolower($search_string));
		$row['type'] = "stop";
		$ret[] = $row;
	}
	if(count($ret) > 0) { return($ret); }

	$query = "select atcocode as id,commonname as label from stops where commonname like '%" . $db->escape_string($search_string) . "%';";
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$row['diff'] = levenshtein($row['label'], $search_string);
		$row['type'] = "stop";
		$ret[] = $row;
	}
	if(count($ret) > 0) { return($ret); }

	$query = "select atcocode as id,commonname as label from stops where commonname like '%" . implode("%' and commonname like '%", explode(" ", $db->escape_string($search_string))) . "%';";
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$row['diff'] = levenshtein($row['label'], $search_string);
		$row['type'] = "stop";
		$ret[] = $row;
	}
	if(count($ret) > 0) { return($ret); }

	$query = "select atcocode as id,commonname as label, street from stops where street like '" . $db->escape_string($search_string) . ", %' or street like '" . $db->escape_string($search_string) . "';";
	$res = $db->query($query);
	$area = array();
	$area['result'] = array();
	while($row = $res->fetch_assoc())
	{
		$area['id'] = $row['street'];
		$area['label'] = $row['street'];
		$area['diff'] = levenshtein($row['street'], $search_string);
		$area['type'] = "street";
		$area['result'][] = $row['id'];
	}
	if(count($area['result']) > 0) { $ret[] = $area; }
	if(count($ret) > 0) { return($ret); }

	return($ret);
}

function search_uri_stops($uri, $db)
{
	$ret = array();
	$g = new Graphite();
	$g->load($uri);
	$res = $g->resource($uri);
	$lat = (float) ("" . $res->get("geo:lat"));
	$lon = (float) ("" . $res->get("geo:long"));
	foreach(get_nearest_stops($lat, $lon, $db) as $item)
	{
		$ret[] = $item['id'];
	}
	return($ret);
}

function route_search($stops_from, $stops_to, $db, $time)
{
	$query = "select distinct atcocode,routeid,fromseq as seq from journeyschedule,journeyroutes,stops where journeyroutes.journeypattern=journeyschedule.journeypattern and journeyschedule.from=stops.atcocode and (stops.atcocode='" . implode("' or stops.atcocode='", $stops_from) . "');";
	$routes_from = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$row['sequence'] = (int) $row['seq'];
		unset($row['seq']);
		$routes_from[] = $row;
	}

	$query = "select distinct atcocode,routeid,toseq as seq from journeyschedule,journeyroutes,stops where journeyroutes.journeypattern=journeyschedule.journeypattern and journeyschedule.to=stops.atcocode and (stops.atcocode='" . implode("' or stops.atcocode='", $stops_to) . "');";
	$routes_to = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$row['sequence'] = (int) $row['seq'];
		unset($row['seq']);
		$routes_to[] = $row;
	}

	$routes = array();
	foreach($routes_from as $rf)
	{
		foreach($routes_to as $rt)
		{
			if(in_array($rf['routeid'], $routes)) { continue; }
			if(strcmp($rt['routeid'], $rf['routeid']) != 0) { continue; }
			if($rf['sequence'] >= $rt['sequence']) { continue; }
			$routes[] = $rf['routeid'];
		}
	}

	return($routes);
}
