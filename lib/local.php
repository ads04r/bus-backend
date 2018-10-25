<?php

function get_locale_info($localename, $db)
{
	$query = "select id,localityname from localities where localityname='" . $db->escape_string($localename) . "' or parentlocalityname='" . $db->escape_string($localename) . "' or grandparentlocalityname='" . $db->escape_string($localename) . "' order by localityname ASC, parentlocalityname ASC, grandparentlocalityname ASC";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$ret[] = $row;
	}
	return($ret);
}

function get_locale_services($localename, $db)
{
	$query = "select distinct operator,service,routeid from routelocality where nptgid in (select distinct id as nptgid from localities where localityname='" . $db->escape_string($localename) . "' or parentlocalityname='" . $db->escape_string($localename) . "' or grandparentlocalityname='" . $db->escape_string($localename) . "')";

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

function get_locale_roads($localename, $db)
{
	$query = "select distinct street from stops, (select * from localities where localityname='" . $db->escape_string($localename) . "' or parentlocalityname='" . $db->escape_string($localename) . "' or grandparentlocalityname='" . $db->escape_string($localename) . "') as locales where locales.id=stops.nptglocalitycode and street<>'' and street<>'-' order by street ASC;";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$ret[] = "" . $row['street'];
	}
	return($ret);
}

function get_locale_stops($localename, $db)
{
	$query = "select distinct atcocode as id, commonname as name, bearing, latitude as lat, longitude as lon from stops, (select * from localities where localityname='" . $db->escape_string($localename) . "' or parentlocalityname='" . $db->escape_string($localename) . "' or grandparentlocalityname='" . $db->escape_string($localename) . "') as locales where locales.id=stops.nptglocalitycode order by commonname ASC;";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$row['latitude'] = (float) $row['lat'];
		$row['longitude'] = (float) $row['lon'];
		unset($row['lat']);
		unset($row['lon']);
		$ret[] = $row;
	}
	return($ret);
}

function get_locale_localities($localename, $db)
{
	$query = "select distinct id,localityname as name from localities where localityname='" . $db->escape_string($localename) . "' or parentlocalityname='" . $db->escape_string($localename) . "' or grandparentlocalityname='" . $db->escape_string($localename) . "' order by name ASC;";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$ret[] = $row;
	}
	return($ret);
}

function get_locality_data($nptg, $db)
{
	$query = "select * from localities where id='" . $db->escape_string($nptg) . "';";
	$ret = array();
	$res = $db->query($query);
	if($row = $res->fetch_assoc())
	{
		$ret = $row;
	}
	$query = "select AVG(latitude) as lat, AVG(longitude) as lon from stops where nptglocalitycode='" . $db->escape_string($nptg) . "';";
	$res = $db->query($query);
	if($row = $res->fetch_assoc())
	{
		$ret['latitude'] = (float) $row['lat'];
		$ret['longitude'] = (float) $row['lon'];
	}
	return($ret);
}

function get_locality_stops($nptg, $db)
{
	$query = "select atcocode as id, latitude as lat, longitude as lon, commonname as name, bearing from stops where nptglocalitycode='" . $db->escape_string($nptg) . "' order by commonname ASC;";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$row['latitude'] = (float) $row['lat'];
		$row['longitude'] = (float) $row['lon'];
		unset($row['lon']);
		unset($row['lat']);
		$ret[] = $row;
	}
	return($ret);
}

function get_locality_places($nptg, $db)
{
	$query = "select distinct fhrsplaces.* from fhrsplaces, stops, placestops where fhrsplaces.id=placestops.fhrsid and placestops.stopid=stops.atcocode and stops.nptglocalitycode='" . $db->escape_string($nptg) . "';";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$row['lat'] = (float) $row['latitude'];
		$row['lon'] = (float) $row['longitude'];
		unset($row['latitude']);
		unset($row['longitude']);
		$ret[] = $row;
	}
	return($ret);
}

function get_locality_operators($nptg, $db)
{
	$query = "select distinct operator.noc, operator.name from routelocality,operator where nptgid='" . $db->escape_string($nptg) . "' and operator=noc";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$ret[] = $row;
	}
	return($ret);
}

function get_locality_routes($nptg, $db)
{
	$query = "select distinct routelocality.operator, routelocality.service, routelocality.routeid, description from route,routelocality,routeschedule,journeypattern where route.id=journeypattern.route and routeschedule.journeypattern=journeypattern.id and nptgid='" . $db->escape_string($nptg) . "' and routelocality.routeid=routeschedule.routeid order by operator, service, description";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$ret[] = $row;
	}
	return($ret);
}

function get_localities($db)
{
	$query = "select id from localities;";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$ret[] = $row['id'];
	}
	return($ret);
}

function index_route_localities($db)
{
	$queries = array();
	foreach(get_localities($db) as $locid)
	{
		error_log($locid);
		$query = "insert ignore into routelocality (nptgid,routeid,operator,service) select distinct '" . $db->escape_string($locid) . "' as nptgid,routeid,operator,service from routeschedule, (select journeypattern as jp from journeyschedule, (select atcocode as sid from stops where nptglocalitycode='" . $db->escape_string($locid) . "') as x where `from`=sid or `to`=sid) as y where journeypattern=jp;";
		$db->query($query);
	}
}
