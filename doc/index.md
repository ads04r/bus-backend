Hampshire Bus API
=================

*Ash Smith*

A RESTful API for getting realtime bus schedules, as well as information on the
bus routes and operators within Southampton and Hampshire.

bus.flarpyland
--------------
If you just want bus times, try http://bus.flarpyland.com for a service that
makes use of this API to provide bus times for everyone, not just programmers.
This service also has times and information in other formats other than JSON.
It's a fork of bus.soton, which I wrote when I worked for the University of
Southampton.

API Documentation
=================

All calls return JSON.

Stops
-----

Stops are, unsurprisingly, bus stops. They are identified uniquely by their
ATCO codes. An example is 1980HAA13668, which is the University of
Southampton main interchange, where all of the Unilink buses go.

To get the basic info for a stop (name, lat/lon, etc)

    /stop/[ATCO_CODE]

To get a list of upcoming buses for a stop

    /stop/[ATCO_CODE]/buses

To get a list of services (and their routes) that serve a particular stop

    /stop/[ATCO_CODE]/services

To get a list of FHRS-registered places in close vicinity of a stop

    /stop/[ATCO_CODE]/places


Localities
----------

Localities are defined in the National Public Transport Gazetteer (NPTG)
dataset. The heirarchy has three levels, and are identified by a code
such as E0041989, the code for Highfield, in Southampton. A locality
can contain any number of bus stops.

To get the basic info (name, location, etc) for a locality

    /locality/[NPTG_CODE]

To get a list of stops within a locality

    /locality/[NPTG_CODE]/stops

To get a list of FHRS-registered places within a locality

    /locality/[NPTG_CODE]/places

To get a list of bus companies operating within a particular locality

    /locality/[NPTG_CODE]/operators

To get a list of routes which intersect a locality

    /locality/[NPTG_CODE]/routes


Locales
-------

Locales are an invention of mine, based on Localities. Locales are identified
by their name only, and are effectively one or more Localities.

To get a list of localities within a locale (generally within the appropriate
heirarchy)

    /locale/[Locale_name]

    eg /locale/Southampton

To get a list of bus stops within a locale

    /locale/[Locale_name]/stops

To get a list of bus services operating within a locale

    /locale/[Locale_name]/services

To get a list of roads containing bus stops within a locale

    /locale/[Locale_name]/roads


Areas
-----

Areas are also an invention of mine, only this time much more specific. An area
is essentially a collection of one or more bus stops, and can be given any name
at all. They are identified by an alphanumeric string, such as 'highfield' or
'centralstation', and should only contain ASCII letters and numbers for
compatibility with as many things as possible.

To get some general information about an area (name, lat/lon, etc)

    /area/[Area_name]

    eg /area/highfield

To get a list of stops within an area

    /area/[Area_name]/stops

To get a list of buses currently in operation within an area (effectively just
a concatenation of schedules for all the stops within an area

    /area/[Area_name]/buses


Operators
---------

An Operator is basically a company that runs buses. They all have a four-
character national operator code (NOC) and it is by these that we identify
them.

To get general information about an operator, including all the services it
runs

    /operator/[NOC]

To get a list of all the routes run by a particular operator

    /operator/[NOC]/routes

To get a list of NPTG localities served by a particular operator

    /operator/[NOC]/localities


Services
--------

A Service is what is commonly known as a bus route or line, identified by a
number or letter (or combination of numbers and letters, such as 'U1').
It is what most people think of when they think of a particular bus route,
although in reality it can have many routes. For example, the Blue Star
number 1 from Southampton to Winchester will travel a slightly different
route at different times of day, and obviously has an inverse route, from
Winchester to Southampton. However these are all the same 'service'.

To get general information about a service, including all the routes it
takes

    /service/[NOC]/[Service_ID]

Where [NOC] is the national operator code of the bus operator and [ID] is the
number on the front of the bus.

Routes
------

Routes are actual bus routes, and are effectively an ordered list of bus stops.
Routes have no time information, but are identified in our implementation by an
MD5 hash. This is a hash of the ATCO codes of all the stops, in order, so if a
stop is missed in a particular route, it changes the hash completely. A
Service can have one or more Routes, and a Route will have one or more
Journeys.

To get basic information about a route, including a general textual
description of the route and the bus operator

    /route/[Hash]

To get an ordered list of stops on a particular route

    /route/[Hash]/stops

To get a list of journeys running today that take a particular route

    /route/[Hash]/journeys


Journeys
--------

A Journey is a specific route taken at a specific time on a specific day.
They are identified by four slash-separated values, 
[operator]/[service]/[date]/[time]. Date is YYYY-MM-DD format, time is 
the start time of the journey in 24hr format and always UTC, otherwise
there is confusion around the start and end of daylight saving time.

To get a general summary of a particular journey, including its route hash,
its start time (as a UTC Unix timestamp) and its duration in seconds

    /journey/[NOC]/[Service_ID]/[YYYY-MM-DD]/[HHMM]

To get a list of stops on a particular journey, as well as the time the bus is
due to arrive at each

    /journey/[NOC]/[Service_ID]/[YYYY-MM-DD]/[HHMM]/stops


Places
------

A Place is any place in the national Food Hygiene dataset, identified by the
code given to them in this data. So, for example, the Hobbit in Southampton
is referred to as 220393, as this is how it is known in the food hygiene
data.

To get all geographical information about a particular place in the FHRS
dataset

    /place/[FHRS_ID]

To get all the bus stops within close vicinity of a FHRS place

    /place/[FHRS_ID]/stops

To get a list of buses currently in operation within the vicinity of a FHRS
place (effectively just a concatenation of schedules for all the stops
nearby)

    /place/[FHRS_ID]/buses


Lat/Lon
-------

A point in space, identified by a latitude/longitude pair.

To dump the nearest ten stops to a geographical point

    /latlon/[LATITUDE]/[LONGITUDE]/stops

To get the NPTG locality information for a geographical point

    /latlon/[LATITUDE]/[LONGITUDE]/locality

To get the human-readable street address of a geographical point

    /latlon/[LATITUDE]/[LONGITUDE]/address


Postcodes
---------

A UK post code. Please note that post codes must consist of letters and
numbers only, and should be all upper case. Spaces are and should be ignored.

To get the latitude and longitude (approximately) of a UK postcode

    /postcode/[POSTCODE]

To dump the nearest ten stops to a postcode

    /postcode/[POSTCODE]/stops


Searching
---------

Searching is still a work in progress. There is currently no reliable way to
determine a route between two places, but finding a place from a string search
is possible. This is done using the /resolve endpoint, called with a POST
request.

    curl --header "Content-Type: application/json" \
         --request POST \
         --data '{"type":"","search":"Archers"}' \
         https://api.bus.flarpyland.com/resolve

This example will return something like...

	[
	   {
		  "query" : "1980SN120405",
		  "label" : "Archers Road",
		  "type" : "stop-area",
		  "result" : [
			 "1980SN120405",
			 "1980SN120406",
			 "1980SN120535",
			 "1980SN120402",
			 "1980SN120536",
			 "1980SN120404",
			 "1980SN120891",
			 "1980SN120510",
			 "1980SNA90894",
			 "1980SN120403",
			 "1980SN120401",
			 "1980SN120407"
		  ]
	   },
	   {
		  "query" : "1980SN120394",
		  "label" : "Archers Road",
		  "type" : "stop",
		  "result" : [
			 "1980SN120394",
			 "1980SNA90918",
			 "1980SN120504",
			 "1980SN120506",
			 "1980SN120505",
			 "1980SN120507",
			 "1980SNA90920",
			 "1980SNA90919",
			 "1980SN120396",
			 "1980SN120395"
		  ]
	   }
	]

Each search result has a query, a label, a type and a result. The query is
basically the ID of the returned item (the 'query' label is legacy,
unfortunately) and the 'result' field is an array of stop IDs. If the type
of the result is a stop, then these are redundant.

Types of returned result can be stop, stop-area, fhrs, postcode or street.


Dumps
-----

This refers to data dumps, not actual dumps to which one might catch a bus :)
They are simply JSON files containing lists and basic data on the routes,
operators and stops within this data. It's a good place to get some IDs
for testing purposes, too.

To dump a list of bus operators

    /dump/operators

To dump a list of bus routes

    /dump/routes

To dump a list of bus stops

    /dump/stops

