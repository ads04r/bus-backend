<?php

function parse_timespan($timespan_string)
{
	$m = 0;
	$s = 0;
	$match = array();
	$ps = preg_replace("/^(.+)T([^T]+)$/", "$2", $timespan_string);
	if((preg_match("/([0-9]+)M/", $ps, $match)) > 0)
	{
		$m = (int) $match[1];
	}
	if((preg_match("/([0-9]+)S/", $ps, $match)) > 0)
	{
		$s = (int) $match[1];
	}
	return(($m * 60) + $s);
}

function get_route_journeys($routeid, $db)
{
	$query = "select distinct * from routeschedule where routeid='" . $db->escape_string($routeid) . "' and journeydate>CURDATE() and journeydate<DATE_ADD(CURDATE(), INTERVAL 24 HOUR) order by journeydate ASC;";
	$res = $db->query($query);
	$ret = array();
	while($row = $res->fetch_assoc())
	{
		$item = array();
		$ds = $row['journeydate'];
		$dt = strtotime($ds);
		$item['id'] = $row['operator'] . "/" . $row['service'] . "/" . date("Y-m-d", $dt) . "/" . date("Hi", $dt);
		$item['date'] = $dt;
		$item['starttime'] = date("H:i", $dt);
		$ret[] = $item;
	}
	return($ret);
}

function get_route_stops($routeid, $db)
{
	$query = "select distinct atcocode as id,commonname,bearing,latitude as lat,longitude as lon from (select distinct fromseq as seq,journeyschedule.`from` as stop from journeyschedule,journeyroutes where journeyroutes.journeypattern=journeyschedule.journeypattern and routeid='" . $db->escape_string($routeid) . "' union select distinct toseq as seq,journeyschedule.`to` as stop from journeyschedule,journeyroutes where journeyroutes.journeypattern=journeyschedule.journeypattern and routeid='" . $db->escape_string($routeid) . "') as sequence, stops where stop=stops.atcocode order by seq ASC;";
	$res = $db->query($query);
	$ret = array();
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

function get_route_info($routeid, $db)
{
	$query = "select distinct routeid as id,routenumber as number,operator,description,direction from journeyschedule,journeyroutes where journeyroutes.journeypattern=journeyschedule.journeypattern and routeid='" . $db->escape_string($routeid) . "';";
	$res = $db->query($query);
	$ret = array();
	$noc = "";
	if($row = $res->fetch_assoc())
	{
		$ret = $row;
		$noc = $row['operator'];
	}
	$operator = array("noc" => $noc);
	$query = "select noc, name from operator where noc='" . $db->escape_string($noc) . "';";
	$res = $db->query($query);
	if($row = $res->fetch_assoc())
	{
		$operator = $row;
	}
	$ret['operator'] = $operator;

	return($ret);
}

function get_service_info($service, $noc, $db)
{
	$ret = array();
	$routes = array();
	$directions = array();
	$operator = array("noc" => $noc);
	$query = "select id, noc, name from operator where noc='" . $db->escape_string($noc) . "';";
	$res = $db->query($query);
	if($row = $res->fetch_assoc())
	{
		$operator = $row;
	}

	$query = "select distinct routeid, description, direction from journeyroutes where operator='" . $db->escape_string($noc) . "' and routenumber='" . $db->escape_string($service) . "' order by description ASC;";
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$item = array();
		$item['id'] = $row['routeid'];
		$item['label'] = $row['description'];
		$item['direction'] = $row['direction'];
		$routes[] = $item;
	}

	$query = "select distinct journeyroutes.routeid, service.description from journeyroutes, journeypattern, service where journeyroutes.journeypattern=journeypattern.id and journeypattern.service=service.id and journeyroutes.operator='" . $db->escape_string($noc) . "' and journeyroutes.routenumber='" . $db->escape_string($service) . "' order by description ASC;";
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$ret['label'] = $row['description'];
	}

	$query = "select distinct direction from schedule where operator='" . $db->escape_string($noc) . "' and service='" . $db->escape_string($service) . "' order by direction ASC;";
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$directions[] = $row['direction'];
	}

	if(array_key_exists("id", $operator)) { unset($operator['id']); }
	$ret['operator'] = $operator;
	$ret['routes'] = $routes;
	$ret['directions'] = $directions;
	if(count($ret) > 0) { $ret['id'] = $service; }
	return($ret);
}

function get_service_stops($service, $noc, $direction, $db)
{
	$dt = time();
	$ret = array();
	$query = "select stop_id, AVG(sequence) as sequence, GROUP_CONCAT(DISTINCT route ORDER BY route ASC SEPARATOR ' ') as routes from schedule where direction='" . $db->escape_string($direction) . "' and operator='" . $db->escape_string($noc) . "' and service='" . $db->escape_string($service) . "' and `date`>'" . date("Y-m-d", $dt) . " 04:00:00' and `date`<'" . date("Y-m-d", ($dt + 86400)) . " 05:00:00' group by stop_id";
	$query = "select sch.*, stops.commonname as label from (" . $query . ") as sch, stops where stops.atcocode=stop_id;";
	$res = $db->query($query);
	while($row = $res->fetch_assoc())
	{
		$item = array();
		$item['id'] = $row['stop_id'];
		$item['label'] = $row['label'];
		$item['routes'] = explode(" ", $row['routes']);
		$item['sequence'] = (int) $row['sequence'];
		$ret[] = $item;
	}
	usort($ret, function($a, $b)
	{
		if($a['sequence'] < $b['sequence']) { return -1; }
		if($a['sequence'] > $b['sequence']) { return 1; }
		return 0;
	});

	return($ret);
}

function parse_days($operating_profile_xml)
{
	return(all_paths($operating_profile_xml, "/", array()));
}

function all_paths($node, $rel, $arr)
{
	foreach($node->children() as $child)
	{
		$arr[] = trim($rel . $child->getName(), "/");
		$text = trim("" . $child);
		if(strlen($text) > 0)
		{
			$arr[] = trim($rel . $child->getName(), "/") . "|" . $text;
		}
		$arr = all_paths($child, $rel . $child->getName() . "/", $arr);
	}
	return($arr);
}

function leaf_node($node)
{
	$children = $node->children();
	if(count($children) == 0)
	{
		return("" . $node->getName());
	}
	return(leaf_node($children[0]));
}

function read_journey_file($file, $data_path, $db=null)
{

		$servicedorganisations = array();
		$stoppoints = array();
		$routesections = array();
		$routelinks = array();
		$routes = array();
		$journeypatternsections = array();
		$journeypatternsectionsprocessed = array();
		$journeypatterns = array();
		$operators = array();
		$services = array();
		$vehiclejourneys = array();

		if(preg_match("/^[^\\.](.*)\\.xml$/", $file) == 0)
		{
			return(array());
		}

		$full_path = $data_path . "/" . $file;
		$xml = simplexml_load_file($full_path);

		// Populate data

		if($xml->ServicedOrganisations->ServicedOrganisation)
		{
			foreach($xml->ServicedOrganisations->ServicedOrganisation as $node)
			{
				$id = "" . $node->OrganisationCode;
				$item = array();
				$dates = array();
				foreach($node->Holidays->DateRange as $datexml)
				{
					$dateitem = array();
					$dateitem['description'] = "" . $datexml->Description;
					$dateitem['start'] = strtotime($datexml->StartDate . " 00:00:00");
					$dateitem['end'] = strtotime($datexml->EndDate . " 23:59:59");
					$dates[] = $dateitem;
				}
				$item['id'] = $id;
				$item['name'] = "" . $node->Name;
				$item['dates'] = $dates;

				$servicedorganisations[$id] = $item;

				if($db != null)
				{
					$query = "insert ignore into organisations (id, label) values ('" . $db->escape_string($item['id']) . "', '" . $db->escape_string($item['name']) . "');";
					$db->query($query);

					foreach($dates as $info)
					{
						$k = array(); $v = array();
						$k[] = "organisation"; $v[] = $db->escape_string($item['id']);
						$k[] = "description"; $v[] = $db->escape_string($info['description']);
						$k[] = "start"; $v[] = gmdate("Y-m-d H:i:s", $info['start']);
						$k[] = "end"; $v[] = gmdate("Y-m-d H:i:s", $info['end']);

						$query = "insert ignore into organisation_dates (" . implode(", ", $k) . ") values ('" . implode("', '", $v) . "');";
						$db->query($query);
					}
				}
			}
		}

		foreach($xml->StopPoints->AnnotatedStopPointRef as $node)
		{
			$id = "" . $node->StopPointRef;
			$item = array();
			$item['stopcode'] = $id;
			$item['name'] = "" . $node->CommonName;
			$item['locality'] = "" . $node->LocalityName;
			$item['area'] = "" . $node->LocalityQualifier;
			$stoppoints[$id] = $item;
		}

		foreach($xml->RouteSections->RouteSection as $node)
		{
			$attr = $node->attributes();
			$id = "" . $attr['id'];
			$links = array();
			foreach($node->RouteLink as $link)
			{
				$item = array();
				$attr = $link->attributes();
				$item['id'] = "" . $attr['id'];
				$item['from'] = "" . $link->From->StopPointRef;
				$item['to'] = "" . $link->To->StopPointRef;
				$item['direction'] = "" . $link->Direction;
				$routelinks[$item['id']] = $item;
				$links[] = $item;
			}
			$routesections[$id] = $links;
		}

		foreach($xml->Routes->Route as $node)
		{
			$attr = $node->attributes();
			$id = "" . $attr['id'];
			$item = array();
			$item['id'] = $id;
			$item['description'] = "" . $node->Description;
			$item['route'] = $routesections["" . $node->RouteSectionRef];
			$routes[$id] = $item;
		}

		foreach($xml->JourneyPatternSections->JourneyPatternSection as $node)
		{
			$attr = $node->attributes();
			$id = "" . $attr['id'];
			$item = array();
			$links = array();
			$times = array();
			$stops = array();
			$activities = array();
			foreach($node->JourneyPatternTimingLink as $tl)
			{
				$titem = array();
				$attr = $tl->attributes();
				$fromxml = $tl->From;
				$toxml = $tl->To;
				$from = array();
				$to = array();
				$wait = 0;

				foreach($fromxml->WaitTime as $wt)
				{
					$wait = $wait + parse_timespan("" . $wt);
				}
				foreach($toxml->WaitTime as $wt)
				{
					$wait = $wait + parse_timespan("" . $wt);
				}

				$fromattr = $fromxml->attributes();
				$toattr = $toxml->attributes();
				$fromseq = "" . $fromattr['SequenceNumber'];
				$toseq = "" . $toattr['SequenceNumber'];
				$from['sequence'] = (int) $fromseq;
				$from['activity'] = "" . $fromxml->Activity;
				$from['timingstatus'] = "" . $fromxml->TimingStatus;
				$from['stop'] = @$stoppoints["" . $fromxml->StopPointRef];
				$to['sequence'] = (int) $toseq;
				$to['activity'] = "" . $toxml->Activity;
				$to['timingstatus'] = "" . $toxml->TimingStatus;
				$to['stop'] = @$stoppoints["" . $toxml->StopPointRef];
				$titem['id'] = "" . $attr['id'];
				$titem['time'] = parse_timespan("" . $tl->RunTime) + $wait;
				$titem['from'] = $from;
				$titem['to'] = $to;
				$titem['routelink'] = $routelinks["" . $tl->RouteLinkRef];
				$links[] = $titem;

				$stops[$fromseq] = $from['stop'];
				$times[$toseq] = $titem['time'];
				$stops[$toseq] = $to['stop'];
				$activities[$fromseq] = $from['activity'];
				$activities[$toseq] = $to['activity'];
			}
			$item['id'] = $id;
			$item['timing'] = $links;
			$journeypatternsections[$id] = $item;

			$item2 = array();
			$item2['id'] = $id;
			$item2['times'] = $times;
			$item2['stops'] = $stops;
			$item2['activities'] = $activities;
			$journeypatternsections2[$id] = $item2;
		}

		foreach($xml->Operators->Operator as $node)
		{
			$attr = $node->attributes();
			$id = "" . $attr['id'];
			$item = array();
			$item['id'] = $id;
			$item['code'] = "" . $node->OperatorCode;
			$item['noc'] = "" . $node->NationalOperatorCode;
			$item['name'] = "" . $node->OperatorShortName;
			$item['trading_name'] = "" . $node->TradingName;
			$item['license_name'] = "" . $node->OperatorNameOnLicense;
			$operators[$id] = $item;
		}

		foreach($xml->Services->Service as $node)
		{
			$id = "" . $node->ServiceCode;
			$item = array();
			$journey = array();
			$item['id'] = $id;
			$item['code'] = "" . $node->Lines->Line->LineName;
			$item['description'] = "" . $node->Description;
			$item['start'] = strtotime($node->OperatingPeriod->StartDate . " 00:00:00");
			$item['end'] = strtotime($node->OperatingPeriod->EndDate . " 00:00:00");
			$item['operator'] = $operators["" . $node->RegisteredOperatorRef];
			$item['origin'] = "" . $node->StandardService->Origin;
			$item['destination'] = "" . $node->StandardService->Destination;

			foreach($node->StandardService->JourneyPattern as $jp)
			{
				$attr = $jp->attributes();
				$jid = "" . $attr['id'];
				$jitem = array();
				$jitem['id'] = $jid;
				$jitem['direction'] = "" . $jp->Direction;
				$jitem['pattern'] = $journeypatternsections2["" . $jp->JourneyPatternSectionRefs];
				$jitem['route'] = $routes["" . $jp->RouteRef];
				$journeypatterns[$jid] = $jitem;
				$journey[] = $jitem;
			}

			foreach($node->StandardService->JourneyPattern as $jp)
			{
				$journey[] = $journeypatternsections2["" . $jp->JourneyPatternSectionRefs];
			}

			// $item['journey'] = $journey;

			$services[$id] = $item;
		}

		foreach($xml->VehicleJourneys->VehicleJourney as $node)
		{
			$id = "" . $node->VehicleJourneyCode;
			$service = $services["" . $node->ServiceRef];
			$item = array();
			$item['id'] = $id;
			$item['time'] = "" . $node->DepartureTime;
			$item['days'] = parse_days($node->OperatingProfile);
			foreach($item['days'] as $path)
			{
				$match = array();
				if(preg_match("#^(.+)/ServicedOrganisationRef\\|([^/\\|]+)$#", $path, $match) == 0)
				{
					continue;
				}
				$item['organisation'] = $servicedorganisations[$match[2]];
			}
			$item['service'] = $service;
			$pattern = $journeypatterns["" . $node->JourneyPatternRef];
			$item['stops'] = $pattern['pattern']['stops'];
			$item['times'] = $pattern['pattern']['times'];
			$item['activities'] = $pattern['pattern']['activities'];
			$item['direction'] = $pattern['direction'];
			$item['jpid'] = $node->JourneyPatternRef . "";
			$vehiclejourneys[$id] = $item;
		}

		// Format data

		foreach($vehiclejourneys as $journey)
		{
			$data[] = $journey;
		}

		return($data);
}

