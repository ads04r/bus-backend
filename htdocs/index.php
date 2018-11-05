<?php

include_once(dirname(dirname(__file__)) . "/lib/bootstrap.php");

include($lib_dir . "/arc2/ARC2.php");
include($lib_dir . "/graphite/Graphite.php");
include($lib_dir . "/valium/valium.php");
include($lib_dir . "/parsedown/Parsedown.php");

$db = new mysqli($cfg['database']['host'], $cfg['database']['username'], $cfg['database']['password'], $cfg['database']['database']);
$v = new Valium();
$v->throw_404_on_error = true;

$v->route("|/route$|", function($m, $get, $post)
{
	if(!((array_key_exists("from", $get)) & (array_key_exists("to", $get))))
	{
		$v->error(404);
	}
	$from = $get['from'];
	$to = $get['to'];

	$route = find_route($from, $to);
	return($route);
});

// stop

$v->route("|/stop/([^/]+)$|", function($m, $get, $post) use (&$db)
{
	$ret = get_stop_data($m[1], $db);
	$ret['lat'] = (float) $ret['latitude'];
	$ret['lon'] = (float) $ret['longitude'];
	unset($ret['latitude']);
	unset($ret['longitude']);
	$locinfo = array();
	if(strlen($ret['nptglocalitycode']) > 0)
	{
		$locinfo = get_locality_data($ret['nptglocalitycode'], $db);
	}
	unset($ret['nptglocalitycode']);
	$ret['locality'] = $locinfo;

	return($ret);
});

$v->route("|/stop/([^/]+)/places$|", function($m, $get, $post) use (&$db)
{
	return(get_stop_places($m[1], $db));
});

$v->route("|/stop/([^/]+)/services$|", function($m, $get, $post) use (&$db)
{
	return(get_stop_services($m[1], $db));
});

$v->route("|/stop/([^/]+)/buses$|", function($m, $get, $post) use (&$db)
{
	return(get_stop_schedule($m[1], $db));
});

// locality - a political locality

$v->route("|/locality/([^/]+)$|", function($m, $get, $post) use (&$db)
{
	return(get_locality_data($m[1], $db));
});

$v->route("|/locality/([^/]+)/stops$|", function($m, $get, $post) use (&$db)
{
	return(get_locality_stops($m[1], $db));
});

$v->route("|/locality/([^/]+)/places$|", function($m, $get, $post) use (&$db)
{
	return(get_locality_places($m[1], $db));
});

$v->route("|/locality/([^/]+)/operators$|", function($m, $get, $post) use (&$db)
{
	return(get_locality_operators($m[1], $db));
});

$v->route("|/locality/([^/]+)/routes$|", function($m, $get, $post) use (&$db)
{
	return(get_locality_routes($m[1], $db));
});

// locale - a geographical area. A term I invented. I'll do something cool with this in version 2.

$v->route("|/locale/([^/]+)$|", function($m, $get, $post) use (&$db)
{
	return(get_locale_info($m[1], $db));
});

$v->route("|/locale/([^/]+)/stops$|", function($m, $get, $post) use (&$db)
{
	return(get_locale_stops($m[1], $db));
});

$v->route("|/locale/([^/]+)/services$|", function($m, $get, $post) use (&$db)
{
	return(get_locale_services($m[1], $db));
});

$v->route("|/locale/([^/]+)/roads$|", function($m, $get, $post) use (&$db)
{
	return(get_locale_roads($m[1], $db));
});

$v->route("|/locale/([^/]+)/localities$|", function($m, $get, $post) use (&$db)
{
	return(get_locale_localities($m[1], $db));
});

// area - an unordered collection of stops

$v->route("|/area/([^/]+)$|", function($m, $get, $post) use (&$db)
{
	return(get_area_info($m[1]));
});

$v->route("|/area/([^/]+)/buses$|", function($m, $get, $post) use (&$db)
{
	return(get_area_buses($m[1], $db));
});

$v->route("|/area/([^/]+)/stops$|", function($m, $get, $post) use (&$db)
{
	return(get_area_stops($m[1]));
});

// route - an ordered collection of stops, identified by an MD5 hash

$v->route("|/route/([^/]+)$|", function($m, $get, $post) use (&$db)
{
	return(get_route_info($m[1], $db));
});

$v->route("|/route/([^/]+)/stops$|", function($m, $get, $post) use (&$db)
{
	return(get_route_stops($m[1], $db));
});

$v->route("|/route/([^/]+)/journeys$|", function($m, $get, $post) use (&$db)
{
	return(get_route_journeys($m[1], $db));
});

$v->route("|/route/([^/]+)/points$|", function($m, $get, $post) use (&$db)
{
	return(get_route_polyline($m[1], $db));
});

// service - a service provided by a bus company (eg U1C) that has several routes

$v->route("|/service/([^/]+)/([^/]+)$|", function($m, $get, $post) use (&$db)
{
	return(get_service_info($m[2], $m[1], $db));
});

$v->route("|/service/([^/]+)/([^/]+)/([^/]+)/stops$|", function($m, $get, $post) use (&$db)
{
	return(get_service_stops($m[2], $m[1], $m[3], $db));
});

// journey - a specific vehicle route, beginning and ending at a specific time on a specific day

$v->route("|/journey/([A-Z]+)/([A-Za-z0-9]+)/([0-9]{4}-[0-9]{2}-[0-9]{2})/([0-9]{4})$|", function($m, $get, $post) use (&$db)
{
	return(get_journey_info($m[1], $m[2], $m[3], $m[4], $db));
});

$v->route("|/journey/([A-Z]+)/([A-Za-z0-9]+)/([0-9]{4}-[0-9]{2}-[0-9]{2})/([0-9]{4})/stops$|", function($m, $get, $post) use (&$db)
{
	return(get_journey_stops($m[1], $m[2], $m[3], $m[4], $db));
});

// operator - a company that runs bus services

$v->route("|/operator/([^/]+)$|", function($m, $get, $post) use (&$db)
{
	return(get_operator_data($m[1], $db));
});

$v->route("|/operator/([^/]+)/routes$|", function($m, $get, $post) use (&$db)
{
	return(get_operator_routes($m[1], $db));
});

$v->route("|/operator/([^/]+)/localities$|", function($m, $get, $post) use (&$db)
{
	return(get_operator_localities($m[1], $db));
});

// place - a fhrs place

$v->route("|/place/([^/]+)$|", function($m, $get, $post) use (&$db)
{
	return(get_place_info($m[1], $db));
});

$v->route("|/place/([^/]+)/stops$|", function($m, $get, $post) use (&$db)
{
	return(get_place_stops($m[1], $db));
});

$v->route("|/place/([^/]+)/buses$|", function($m, $get, $post) use (&$db)
{
	return(get_place_buses($m[1], $db));
});

// latlon - a latitude/longitude point

$v->route("|/latlon/([0-9\\.\\-]+)/([0-9\\.\\-]+)/stops|", function($m, $get, $post) use (&$db)
{
	$lat = (float) $m[1];
	$lon = (float) $m[2];
	return(get_nearest_stops($lat, $lon, $db));
});
$v->route("|/latlon/([0-9\\.\\-]+)/([0-9\\.\\-]+)/locality|", function($m, $get, $post) use (&$db)
{
	$lat = (float) $m[1];
	$lon = (float) $m[2];
	return(get_nearest_locality($lat, $lon, $db));
});
$v->route("|/latlon/([0-9\\.\\-]+)/([0-9\\.\\-]+)/address|", function($m, $get, $post) use (&$db)
{
	$lat = (float) $m[1];
	$lon = (float) $m[2];
	return(get_address($lat, $lon, $db));
});

// postcode - a UK post code

$v->route("|/postcode/([0-9A-Za-z]+)$|", function($m, $get, $post) use (&$db)
{
	return(get_postcode($m[1], $db));
});
$v->route("|/postcode/([0-9A-Za-z]+)/stops$|", function($m, $get, $post) use (&$db)
{
	return(get_postcode_stops($m[1], $db));
});

// search - does what it says on the tin

$v->route("|/(resolve)$|", function($m, $get, $post) use (&$db)
{
	$postdata = json_decode(file_get_contents('php://input'), true);

	$params = $postdata;
	if(!(is_array($postdata)))
	{
		$params = array("search"=>$postdata, "type"=>array());
	}

	if(!((array_key_exists("search", $postdata)) & (array_key_exists("type", $postdata))))
	{
		$ret['error'] = "Sorry, need a 'search' and a 'type' in order to function.";
		return($ret);
	}

	return(resolve_stops($postdata['search'], $postdata['type'], $db));
});
$v->route("|/(search)$|", function($m, $get, $post) use (&$db)
{
	$postdata = json_decode(file_get_contents('php://input'), true);
	$ret = array();
	$ret['error'] = "";
	$ret['result'] = array();
	if(!((array_key_exists("from", $postdata)) & (array_key_exists("to", $postdata))))
	{
		$ret['error'] = "Sorry, need a 'from' and a 'to' in order to function.";
		return($ret);
	}
	return(route_search($postdata['from'], $postdata['to'], $db, time()));
});

// dump - handle the dumps (only really used by data.soton)

$v->route("|/dump/([a-z]+)$|", function($m, $get, $post) use (&$db)
{
	if(strcmp($m[1], "operators") == 0)
	{
		return(dump_operators($db));
	}
	if(strcmp($m[1], "stops") == 0)
	{
		return(dump_stops($db));
	}
	if(strcmp($m[1], "routes") == 0)
	{
		return(dump_routes($db));
	}
	return(array());
});

$v->route("|^/$|", function($m, $get, $post)
{
	$doc_dir = dirname(dirname(__file__)) . "/doc";
	$etc_dir = dirname(dirname(__file__)) . "/etc";

	$p = new Parsedown();
	$html = file_get_contents($etc_dir . "/template.html");
	return(str_replace("[[CONTENT]]", $p->text(file_get_contents($doc_dir . "/index.md")), $html) );
});

// Misc Valium stuff

$output = $v->run();
if(is_array($output))
{
	header("Content-type: application/json");
	print(json_encode($output, true));
	exit();
}

print($output);
