<?php

# This file is a part of MyBB RESTful API System plugin - version 0.2
# Released under the MIT Licence by medbenji (TheGarfield)
# 
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

/**
This interface should be implemented by APIs, see VersionAPI for a simple example.
*/
class FileDeleteAPI extends RESTfulAPI {
	
	public function info() {
		return array(
			"name" => "File delete",
			"description" => "This API allows users to delete files and directories.",
			"default" => "deactivated"
		);
	}
	/**
	This is where you perform the action when the API is called, the parameter given is an instance of stdClass, this method should return an instance of stdClass.
	*/
	public function action() {
		global $mybb, $db;
		include "inc/plugins/restfulapi/functions/filefunctions.php";
		include "inc/plugins/restfulapi/functions/varfunctions.php";
		include "inc/plugins/restfulapi/functions/stringfunctions.php";
		$configFileLocation = $mybb->settings["apifilelocation"];
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
		if (!checkIfTraversal($configFileLocation.$phpData["location"], $configFileLocation)) {
			throw new BadRequestException("Directory traversal check failed, or location doesn't exist.");
		}
		if (!checkIfSetAndString($phpData["location"])) {
			throw new BadRequestException("\"location\" key missing.");
		}
		if ($phpContentType !== "application/json") {
			throw new BadRequestException("\"content-type\" header missing, or not \"application/json\".");
		}
		$realLocation = realpath($configFileLocation.$phpData["location"]);
		if (is_dir($realLocation)) {
			if (rmdir($realLocation)) {
				$stdClass->result = returnSuccess($phpData["location"]);
			} else {
				throw new BadRequestException("Unable to delete directory.");
			}
		} else {
			if (unlink($realLocation)) {
				$stdClass->result = returnSuccess($phpData["location"]);
			} else {
				throw new BadRequestException("Unable to delete file.");
			}
		}
		return $stdClass;
	}

	public function activate() {
	}
	
	public function deactivate() {
	}
}
