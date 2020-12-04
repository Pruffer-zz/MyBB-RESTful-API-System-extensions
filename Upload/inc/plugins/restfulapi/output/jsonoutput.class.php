<?php

# This file is a part of MyBB RESTful API System plugin - version 0.2
# Released under the MIT Licence by medbenji (TheGarfield)
# Modified by Prüffer (avantheim.org)

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

/**
This interface should be implemented by Output options, see JSONOutput for a simple example.
*/
class JSONOutput extends RESTfulOutput {
	/**
	This is where you output the object you receive, the parameter given is an instance of stdClass.
	*/
	public function action($stdClassObject) {
		header("Content-type: application/json");
		print json_encode((array)$stdClassObject);
	}
}
