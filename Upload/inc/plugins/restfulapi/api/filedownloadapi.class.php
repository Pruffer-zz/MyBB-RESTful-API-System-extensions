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
class FileDownloadAPI extends RESTfulAPI {
	
	public function info() {
		return array(
			"name" => "File download",
			"description" => "This API allows users to download files to the server.",
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
		if (!checkIfFilenameDirectory(dirname($configFileLocation.$phpData["location"].$phpData["filename"]), $configFileLocation.$phpData["location"])) {
			throw new BadRequestException("\"filename\" key contains a directory.");
		}
		if (!checkIfSetAndString($phpData["location"]) || !checkIfSetAndString($phpData["filename"]) || !checkIfSetAndString($phpData["file"])) {
			throw new BadRequestException("\"location\", \"filename\" or \"file\" key missing.");
		}
		if ($phpContentType !== "application/json") {
			throw new BadRequestException("\"content-type\" header missing, or not \"application/json\".");
		}
		$realLocation = realpath($configFileLocation.$phpData["location"])."/";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $phpData["file"]);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($curl);
		curl_close($curl);
		if (file_exists($realLocation.$phpData["filename"]) && $phpData["overwrite"] === "no") {
			$phpData["filename"] = time().".".$phpData["filename"];
			while (file_exists($realLocation.$phpData["filename"])) {
				$phpData["filename"] = substr(md5(microtime()),rand(0,26),5).time().".".$phpData["filename"];
			}
		}
		if ($file = fopen($realLocation.$phpData["filename"], "w")) {
			fwrite($file, $result);
			fclose($file);
			$stdClass->result = returnSuccess($phpData["filename"]);
		} else {
			throw new BadRequestException("File write failed.");
		}
		return $stdClass;
	}

	public function activate() {
	}
	
	public function deactivate() {
	}
}
