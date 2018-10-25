<?php

function distance($lat1, $lon1, $lat2, $lon2)
{
	$pi = 3.1415927;
	$r = 6371; // earth circumference in km
	$dLat = ($lat2-$lat1) * ($pi / 180);
	$dLon = ($lon2-$lon1) * ($pi / 180);
	$ll1 = $lat1 * ($pi / 180);
	$ll2 = $lat2 * ($pi / 180);
	$a = sin($dLat/2) * sin($dLat/2) + sin($dLon/2) * sin($dLon/2) * cos($ll1) * cos($ll2);
	$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
	$d = $r * $c;

	return $d;
}
