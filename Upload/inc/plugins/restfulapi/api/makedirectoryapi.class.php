<?php

# This file is a part of MyBB RESTful API System plugin - version 0.2
# Released under the MIT Licence by medbenji (TheGarfield)
# Extension released by Prüffer (avantheim.org) under the GNU General Public License v3.0

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

/**
This interface should be implemented by APIs, see VersionAPI for a simple example.
*/
class MakeDirectoryAPI extends RESTfulAPI {
	
	public function info() {
		return array(
			"name" => "Make directory",
			"description" => "This API allows users to make directories.",
			"default" => "deactivated"
		);
	}
	/**
	This is where you perform the action when the API is called, the parameter given is an instance of stdClass, this method should return an instance of stdClass.
	*/
	public function action() {
		global $mybb, $db;
		include "inc/plugins/restfulapi/functions/filefunctions.php";
		include "inc/plugins/restfulapi/functions/jsonfunctions.php";
		include "inc/plugins/restfulapi/functions/stringfunctions.php";
		$configFileLocation = $mybb->settings["apifilelocation"];
		$stdClass = new stdClass();
		$rawBody = file_get_contents("php://input");
		if (!($body = checkIfJson($rawBody))) {
			throw new BadRequestException("Invalid JSON data.");
		}
		$phpLocation = getKeyValue("location", $body);
		$phpContentType = $_SERVER["CONTENT_TYPE"];
		if (!checkIfTraversal(dirname($configFileLocation.$phpLocation), $configFileLocation)) {
			$error = ("Directory traversal check failed, or parent directory doesn't exist.");
		}
		if (!checkIfSetAndString($phpLocation)) {
			$error = ("\"location\" key missing.");
		}
		if ($phpContentType !== "application/json") {
			$error = ("\"content-type\" header missing, or not \"application/json\".");
		}
		if (file_exists($configFileLocation.$phpLocation)) {
			$error = ("Directory / file already exists.");
		}
		if ($error) {
			throw new BadRequestException($error);
		}
		if (mkdir($configFileLocation.$phpLocation)) {
			$stdClass->result = returnSuccess($phpLocation);
		} else {
			throw new BadRequestException("Directory creation failed");
		}
		return $stdClass;
	}

	public function activate() {
	}
	
	public function deactivate() {
	}
}
