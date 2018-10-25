<?php

function throw404()
{
	header("HTTP/1.0 404 Not Found");

	print("<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n");
	print("<html><head>\n");
	print("<title>404 Not Found</title>\n");
	print("</head><body>\n");
	print("<h1>Not Found</h1>\n");
	print("<p>The requested URL " . $_SERVER['REQUEST_URI'] . " was not found on this server.</p>\n");
	print("<p>Additionally, a 404 Not Found\n");
	print("error was encountered while trying to use an ErrorDocument to handle the request.</p>\n");
	print("</body></html>\n");

	exit();

}
