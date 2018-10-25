<?php

/*
This function is necessary because Unilink's "official" listings don't have the actual bus numbers on them.
They simply have 'U1', 'U2', 'U6', etc without distinguishing between 'U1C', 'U1A', 'U1E', etc. Luckily, we
can easily determine this by looking at the bus's final destination.
*/

function soton_extra($db)
{
	$query = "update journeyroutes set routenumber='U6H' where routenumber='U6' and `to`='1980SNA19482';";
	$db->query($query);
	$query = "update journeyroutes set routenumber='U6C' where routenumber='U6' and `to`='1980HAA13579';";
	$db->query($query);
	$query = "update journeyroutes set routenumber='U6C' where routenumber='U6' and `to`='1980HAA13583';";
	$db->query($query);
	$query = "update journeyroutes set routenumber='U2C' where routenumber='U2' and `to`='1980SNA90926';";
	$db->query($query);
	$query = "update journeyroutes set routenumber='U2B' where routenumber='U2' and `to`='1980SNA09298';";
	$db->query($query);
	$query = "update journeyroutes set routenumber='U1A' where routenumber='U1' and `to`='1900HA030183';";
	$db->query($query);
	$query = "update journeyroutes set routenumber='U1E' where routenumber='U1' and `to`='1900HA030212';";
	$db->query($query);
	$query = "update journeyroutes set routenumber='U1C' where routenumber='U1' and `to`='1980SN120520';";
	$db->query($query);
	$query = "update journeyroutes set routenumber='U1C' where routenumber='U1' and `to`='1980HAA13579';";
	$db->query($query);
	$query = "update journeyroutes set routenumber='U1W' where routenumber='U1' and `to`='1980SN120160';";
	$db->query($query);
}
