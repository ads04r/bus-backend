<?php

function dump_operators($db)
{
	$query = "select distinct noc as id, tradingname as label from operator, service where service.operator=operator.id order by tradingname ASC;";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$ret[] = $row;
	}
	return($ret);
}

function dump_stops($db)
{
	$query = "select atcocode as id, commonname as label, latitude as lat, longitude as lon from stops order by commonname ASC;";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$ret[] = $row;
	}
	return($ret);
}

function dump_routes($db)
{
	$stops = array();
	$query = "select distinct routeid as id, operator, direction, description as label, routenumber as service from journeyroutes order by operator ASC, routenumber ASC, description ASC;";
	$ret = array();
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$row['stops'] = array();
		foreach(get_route_stops($row['id'], $db) as $stop)
		{
			$row['stops'][] = $stop['id'];
		}
		$ret[] = $row;
	}
	return($ret);
}
