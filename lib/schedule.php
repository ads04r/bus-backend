<?php

/*
select * from (
	select vehiclejourney.id, journeypattern.id as journeypattern, `lines`.name as service_number,departuretime,direction,startdate,enddate,noc,operator.name as operator,origin,destination,description,profile
	from vehiclejourney, journeypattern, service, `lines`, operator
	where vehiclejourney.journeypattern=journeypattern.id and vehiclejourney.service=service.id and vehiclejourney.line=`lines`.id and service.operator=operator.id
) as x, (
	select journeypattern.id as journeypattern,journeypatterntiminglink.`from`,journeypatterntiminglink.`to`,fromactivity,toactivity,fromtimingstatus,totimingstatus,runtime,fromseq,toseq
	from journeypattern,journeypatternsection,route,routesection,routelink,journeypatterntiminglink
	where routelink.routesection=routesection.id and routesection.route=route.id and journeypattern.route=route.id and journeypatternsection.journeypattern=journeypattern.id and journeypatterntiminglink.journeypatternsection=journeypatternsection.id
) as y
where y.journeypattern=x.journeypattern
*/

function get_days_of_operation($start_day, $end_day, $bank_holidays, $school_holidays, $profile)
{
	$days = array();
	$ret = array();
	$rules = explode("\n", trim($profile));

	for($dt = $start_day; $dt <= $end_day; $dt = $dt + 86400)
	{
		$ds = date("Y-m-d", $dt);
		$days[$ds] = 'n';
	}

	// RegularDayType
	foreach($rules as $path)
	{
		$m = array();
		if(preg_match("|^RegularDayType/DaysOfWeek/([a-zA-Z]+)$|", $path, $m) == 0)
		{
			continue;
		}
		$dwc = substr(strtolower($m[1]), 0, 3);
		foreach(array_keys($days) as $ds)
		{
			$dt = strtotime($ds . " 12:00:00");
			$dw = substr(strtolower(date("l", $dt)), 0, 3);
			if(strcmp($dw, $dwc) == 0)
			{
				$days[$ds] = "y";
			}
			if(strcmp(strtolower($m[1]), "mondaytofriday") == 0)
			{
				if((strcmp($dw, "sat") != 0) & (strcmp($dw, "sun") != 0))
				{
					$days[$ds] = "y";
				}
			}
		}
	}

	// Bank Holidays
	// Days of operation...
	foreach($rules as $path)
	{
		$m = array();
		if(preg_match("#^BankHolidayOperation/DaysOfOperation/(.+)$#", $path, $m) == 0)
		{
			continue;
		}
		$hol_info = $m[1];
		if(preg_match("#^OtherPublicHoliday/Date\\|([0-9]+)\\-([0-9]+)\\-([0-9]+)$#", $hol_info, $m) > 0)
		{
			$date = $m[1] . "-" . $m[2] . "-" . $m[3];
			$days[$date] = "y";
			continue;
		}
		if(array_key_exists($hol_info, $bank_holidays))
		{
			foreach($bank_holidays[$hol_info] as $date)
			{
				$days[$date] = "y";
			}
			continue;
		}
		if(strcmp($hol_info, "AllBankHolidays") == 0)
		{
			foreach($bank_holidays as $bh_key)
			{
				foreach($bh_key as $date)
				{
					$days[$date] = "y";
				}
			}
			continue;
		}
	}
	// Days of non-operation...
	foreach($rules as $path)
	{
		$m = array();
		if(preg_match("#^BankHolidayOperation/DaysOfNonOperation/(.+)$#", $path, $m) == 0)
		{
			continue;
		}
		$hol_info = $m[1];
		if(preg_match("#^OtherPublicHoliday/Date\\|([0-9]+)\\-([0-9]+)\\-([0-9]+)$#", $hol_info, $m) > 0)
		{
			$date = $m[1] . "-" . $m[2] . "-" . $m[3];
			$days[$date] = "n - bh";
			continue;
		}
		if(array_key_exists($hol_info, $bank_holidays))
		{
			foreach($bank_holidays[$hol_info] as $date)
			{
				$days[$date] = "n - bh";
			}
			continue;
		}
		if(strcmp($hol_info, "AllBankHolidays") == 0)
		{
			foreach($bank_holidays as $bh_key)
			{
				foreach($bh_key as $date)
				{
					$days[$date] = "n - bh";
				}
			}
			continue;
		}
	}

	// Uni holidays
	// Days of operation...
	foreach($rules as $path)
	{
		$m = array();
		if(preg_match("#^ServicedOrganisationDayType/DaysOfOperation/Holidays/ServicedOrganisationRef\\|(.+)$#", $path, $m) == 0)
		{
			continue;
		}
		$oid = $m[1];
		if(!(array_key_exists($oid, $school_holidays))) { continue; }
		foreach($school_holidays[$oid] as $holiday)
		{
			$dt = strtotime($holiday['start'] . " 00:00:00");
			for($dt = strtotime($holiday['start'] . " 00:00:00"); $dt <= strtotime($holiday['end'] . " 00:00:00"); $dt = $dt + 86400)
			{
				$date = date("Y-m-d", $dt);
				if(array_key_exists($date, $days))
				{
					$days[$date] = "y";
				}
			}
		}
	}
	// Days of non-operation...
	foreach($rules as $path)
	{
		$m = array();
		if(preg_match("#^ServicedOrganisationDayType/DaysOfNonOperation/Holidays/ServicedOrganisationRef\\|(.+)$#", $path, $m) == 0)
		{
			continue;
		}
		$oid = $m[1];
		if(!(array_key_exists($oid, $school_holidays))) { continue; }
		foreach($school_holidays[$oid] as $holiday)
		{
			$dt = strtotime($holiday['start'] . " 00:00:00");
			for($dt = strtotime($holiday['start'] . " 00:00:00"); $dt <= strtotime($holiday['end'] . " 00:00:00"); $dt = $dt + 86400)
			{
				$date = date("Y-m-d", $dt);
				$days[$date] = "n - uni";
			}
		}
	}

	foreach($days as $ds=>$v)
	{
		if(strcmp(strtolower($v), "y") == 0)
		{
			$ret[] = $ds;
		}
	}

	return($ret);
}

function generate_schedule_db($db)
{
	$bh_file = dirname(dirname(__FILE__)) . "/etc/bank_holidays.json";
	$bank_holidays = json_decode(file_get_contents($bh_file), true);

	$query = "";
}

function generate_schedule($routes, $bank_holidays)
{
	$schedule = array();

	foreach($routes as $journey)
	{
		$days = array();

		$start_day = $journey['service']['start'];
		$end_day = $journey['service']['end'];
		for($dt = $start_day; $dt <= $end_day; $dt = $dt + 86400)
		{
			$ds = date("Y-m-d", $dt);
			$days[$ds] = 'n';
		}

		// RegularDayType
		foreach($journey['days'] as $path)
		{
			$m = array();
			if(preg_match("|^RegularDayType/DaysOfWeek/([a-zA-Z]+)$|", $path, $m) == 0)
			{
				continue;
			}
			$dwc = substr(strtolower($m[1]), 0, 3);
			foreach(array_keys($days) as $ds)
			{
				$dt = strtotime($ds . " 12:00:00");
				$dw = substr(strtolower(date("l", $dt)), 0, 3);
				if(strcmp($dw, $dwc) == 0)
				{
					$days[$ds] = "y";
				}
				if(strcmp(strtolower($m[1]), "mondaytofriday") == 0)
				{
					if((strcmp($dw, "sat") != 0) & (strcmp($dw, "sun") != 0))
					{
						$days[$ds] = "y";
					}
				}
			}
		}

		// Bank Holidays
		// Days of operation...
		foreach($journey['days'] as $path)
		{
			$m = array();
			if(preg_match("#^BankHolidayOperation/DaysOfOperation/(.+)$#", $path, $m) == 0)
			{
				continue;
			}
			$hol_info = $m[1];
			if(preg_match("#^OtherPublicHoliday/Date\\|([0-9]+)\\-([0-9]+)\\-([0-9]+)$#", $hol_info, $m) > 0)
			{
				$date = $m[1] . "-" . $m[2] . "-" . $m[3];
				$days[$date] = "y";
				continue;
			}
			if(array_key_exists($hol_info, $bank_holidays))
			{
				foreach($bank_holidays[$hol_info] as $date)
				{
					$days[$date] = "y";
				}
				continue;
			}
			if(strcmp($hol_info, "AllBankHolidays") == 0)
			{
				foreach($bank_holidays as $bh_key)
				{
					foreach($bh_key as $date)
					{
						$days[$date] = "y";
					}
				}
				continue;
			}
		}
		// Days of non-operation...
		foreach($journey['days'] as $path)
		{
			$m = array();
			if(preg_match("#^BankHolidayOperation/DaysOfNonOperation/(.+)$#", $path, $m) == 0)
			{
				continue;
			}
			$hol_info = $m[1];
			if(preg_match("#^OtherPublicHoliday/Date\\|([0-9]+)\\-([0-9]+)\\-([0-9]+)$#", $hol_info, $m) > 0)
			{
				$date = $m[1] . "-" . $m[2] . "-" . $m[3];
				$days[$date] = "n - bh";
				continue;
			}
			if(array_key_exists($hol_info, $bank_holidays))
			{
				foreach($bank_holidays[$hol_info] as $date)
				{
					$days[$date] = "n - bh";
				}
				continue;
			}
			if(strcmp($hol_info, "AllBankHolidays") == 0)
			{
				foreach($bank_holidays as $bh_key)
				{
					foreach($bh_key as $date)
					{
						$days[$date] = "n - bh";
					}
				}
				continue;
			}
		}

		// Uni holidays
		// Days of operation...
		foreach($journey['days'] as $path)
		{
			$m = array();
			if(preg_match("#^ServicedOrganisationDayType/DaysOfOperation/Holidays/ServicedOrganisationRef\\|(.+)$#", $path, $m) == 0)
			{
				continue;
			}
			foreach($journey['organisation']['dates'] as $holiday)
			{
				$dt = $holiday['start'];
				for($dt = $holiday['start']; $dt <= $holiday['end']; $dt = $dt + 86400)
				{
					$date = date("Y-m-d", $dt);
					if(array_key_exists($date, $days))
					{
						$days[$date] = "y";
					}
				}
			}
		}
		// Days of non-operation...
		foreach($journey['days'] as $path)
		{
			$m = array();
			if(preg_match("#^ServicedOrganisationDayType/DaysOfNonOperation/Holidays/ServicedOrganisationRef\\|(.+)$#", $path, $m) == 0)
			{
				continue;
			}
			foreach($journey['organisation']['dates'] as $holiday)
			{
				$dt = $holiday['start'];
				for($dt = $holiday['start']; $dt <= $holiday['end']; $dt = $dt + 86400)
				{
					$date = date("Y-m-d", $dt);
					$days[$date] = "n - uni";
				}
			}
		}

		$m = array();
		$journey['journey'] = 0;
		if(preg_match("/VJ_([0-9]+)-([0-9A-Za-z]+)-_-([0-9A-Za-z]+)-([0-9]+)-([0-9]+)-([0-9A-Za-z]+)/", $journey['id'], $m) > 0)
		{
			$journey['journey'] = (int) $m[5];
		}

		foreach($days as $k=>$v)
		{
			if(strcmp($v, "y") != 0)
			{
				continue;
			}
			$min = 999;
			$max = 0;
			foreach($journey['stops'] as $kk=>$vv)
			{
				$ikk = (int) $kk;
				if($ikk < $min) { $min = $ikk; }
				if($ikk > $max) { $max = $ikk; }
			}

			$ds = $k . " " . $journey['time'];
			$dt = strtotime($ds);

			$origin = $journey['stops'][$min]['name'];
			$destination = $journey['stops'][$max]['name'];

			$item = array();
			$item['id'] = $journey['id'];
			$item['journey'] = $journey['journey'];
			$item['sequence'] = $min;
			$item['route'] = $journey['service']['code'];
			$item['operator'] = $journey['service']['operator']['id'];
			$item['origin'] = $origin;
			$item['destination'] = $destination;
			$item['description'] = $journey['service']['description'];
			$item['direction'] = $journey['direction'];
			$item['date'] = $dt;
			$item['time'] = date("H:i", $dt);
			$item['stopcode'] = $journey['stops'][$min]['stopcode'];
			$item['stopname'] = $journey['stops'][$min]['name'];
			$item['activity'] = $journey['activities'][$min];
			$schedule[] = $item;

			for($i = ($min + 1); $i <= $max; $i++)
			{
				if(!(array_key_exists($i, $journey['times'])))
				{
					continue;
				}
				$dt = $dt + $journey['times'][$i];

				$item = array();
				$item['id'] = $journey['id'];
				$item['journey'] = $journey['journey'];
				$item['sequence'] = $i;
				$item['route'] = $journey['service']['code'];
				$item['operator'] = $journey['service']['operator']['id'];
				$item['origin'] = $origin;
				$item['destination'] = $destination;
				$item['description'] = $journey['service']['description'];
				$item['direction'] = $journey['direction'];
				$item['date'] = $dt;
				$item['time'] = date("H:i", $dt);
				$item['stopcode'] = $journey['stops'][$i]['stopcode'];
				$item['stopname'] = $journey['stops'][$i]['name'];
				$item['activity'] = $journey['activities'][$i];
				$schedule[] = $item;
			}
		}
	}

	return($schedule);
}
