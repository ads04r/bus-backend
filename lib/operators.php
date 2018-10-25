<?php

function get_operator_data($noc, $db)
{
	$query = "select * from operator where noc='" . $db->escape_string($noc) . "';";
	$ret = array();
	$res = $db->query($query);
	if($row = $res->fetch_assoc())
	{
		$ret = $row;
	}
	$ret['services'] = get_operator_services($noc, $db);
	unset($ret['id']);
	return($ret);
}

function get_operator_services($noc, $db)
{
	$query = "select distinct service from routeschedule where operator='" . $db->escape_string($noc) . "' order by service ASC;";
	$query = "select distinct routenumber from journeyroutes where operator='" . $db->escape_string($noc) . "' order by routenumber ASC;";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$ret[] = $row['routenumber'];
	}
	return($ret);
}

function get_operator_routes($noc, $db)
{
	$query = "select distinct routenumber, routeid as id, description as label from journeyroutes where operator='" . $db->escape_string($noc) . "' order by routenumber ASC, description ASC;";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$ret[] = $row;
	}
	return($ret);
}

function get_operator_localities($noc, $db)
{
	$query = "select distinct localities.* from stops, localities, (select `from`,`to` from journeyschedule,routeschedule where journeyschedule.journeypattern=routeschedule.journeypattern and operator='" . $db->escape_string($noc) . "') as x where (x.`from`=stops.atcocode or x.`to`=stops.atcocode) and stops.nptglocalitycode=localities.id;";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$item = array();
		$item['id'] = $row['id'];
		$item['label'] = $row['localityname'];
		$ret[] = $item;
	}
	return($ret);
}
