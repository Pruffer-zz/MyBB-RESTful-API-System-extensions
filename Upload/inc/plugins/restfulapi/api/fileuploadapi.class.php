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
class FileUploadAPI extends RESTfulAPI {
	
	public function info() {
		return array(
			"name" => "File upload",
			"description" => "This API allows users to upload files.",
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
		$rawBody = $_POST["json"];
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
		$phpFile = $_FILES['file']['tmp_name'];
		$phpContentType = $_SERVER["CONTENT_TYPE"];
		if (!checkIfTraversal($configFileLocation.$phpData["location"], $configFileLocation)) {
			throw new BadRequestException("Directory traversal check failed, or location doesn't exist.");
		}
		if (!checkIfFilenameDirectory(dirname($configFileLocation.$phpData["location"].$phpData["filename"]), $configFileLocation.$phpData["location"])) {
			throw new BadRequestException("\"filename\" key contains a directory.");
		}
		if (!checkIfSetAndString($phpData["location"]) || !checkIfSetAndString($phpData["filename"])) {
			throw new BadRequestException("\"location\" or \"filename\" key missing.");
		}
		if (!isset($_FILES['file'])) {
			throw new BadRequestException("\"file\" file missing.");
		}
		if (!strpos($phpContentType, "multipart/form-data") === 0) {
			throw new BadRequestException("\"content-type\" header missing, or doesn't start with \"multipart/form-data\".");
		}
		$realLocation = realpath($configFileLocation.$phpData["location"])."/";
		if (file_exists($realLocation.$phpData["filename"]) && $phpData["overwrite"] === "no") {
			$phpData["filename"] = time().".".$phpData["filename"];
			while (file_exists($realLocation.$phpData["filename"])) {
				$phpData["filename"] = substr(md5(microtime()),rand(0,26),5).time().".".$phpData["filename"];
			}
		}
		if (move_uploaded_file($phpFile, $realLocation.$phpData["filename"])) {
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
