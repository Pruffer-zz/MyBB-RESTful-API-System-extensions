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
This interface should be implemented by APIs, see VersionAPI for a simple example.
*/
class DateAPI extends RESTfulAPI {
	
	public function info() {
		return array(
			"name" => "Date",
			"description" => "This API exposes date utility, very useful for external systems.",
			"default" => "activated"
		);
	}
	/**
	This is where you perform the action when the API is called, the parameter given is an instance of stdClass, this method should return an instance of stdClass.
	*/
	public function action() {
		global $mybb;
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/varfunctions.php";
		$stdClass = new stdClass();
		$phpData = array();
		$rawBody = file_get_contents("php://input");
		if (!($body = checkIfJson($rawBody))) {
			throw new BadRequestException("Invalid JSON data.");
		}
		try {
			foreach($body as $key=>$data) {
				$phpData[$key] = $data;
			}
		}
		catch (Exception $e) {
			throw new BadRequestException("Unable to read JSON data.");
		}
		$phpContentType = $_SERVER["CONTENT_TYPE"];
		if ($phpContentType !== "application/json") {
			throw new BadRequestException("\"content-type\" header missing, or not \"application/json\"");
		}
		$timestamp = "";
		if(checkIfSetAndString($phpData["timestamp"])) {
			$timestamp = (string) $phpData["timestamp"];
		}
		$ty = 1;
		if(checkIfSetAndInArray($phpData["ty"], array("0", "1"))) {
			$ty = (int) $mybb->input["ty"];
		}
		$stdClass->date = my_date($mybb->settings['dateformat'], $timestamp, "", $ty);
		$stdClass->time = my_date($mybb->settings['timeformat'], $timestamp, "", $ty);
		if ($timestamp !== "") {
			$stdClass->timestamp = $timestamp;
		}
		return $stdClass;
	}

}
