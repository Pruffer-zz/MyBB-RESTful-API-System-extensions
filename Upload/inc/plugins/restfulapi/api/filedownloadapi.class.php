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
			"description" => "This API allows users to download files to a location specified in config/filedirectoryconfig.php.",
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
		$configFileLocation = include "inc/plugins/restfulapi/config/filedirectoryconfig.php";
		$stdClass = new stdClass();
		$rawBody = file_get_contents("php://input");
		if (!($body = checkIfJson($rawBody))) {
			$stdClass->result = returnError("Invalid JSON data");
			return $stdClass;
		}
		$phpFile = getKeyValue("file", $body);
		$phpLocation = getKeyValue("location", $body);
		$phpOverwrite = getKeyValue("overwrite", $body);
		$phpFilename = getKeyValue("filename", $body);
		$phpContentType = $_SERVER["CONTENT_TYPE"];
		if (!checkIfTraversal($configFileLocation.$phpLocation, $configFileLocation)) {
			$error = ("Directory traversal check failed, or location doesn't exist");
		}
		if (!checkIfFilenameDirectory(dirname($configFileLocation.$phpLocation.$phpFilename), $configFileLocation.$phpLocation)) {
			$error = ("\"filename\" key contains a directory");
		}
		if (!checkIfSetAndString($phpLocation) || !checkIfSetAndString($phpFilename) || !checkIfSetAndString($phpFile)) {
			$error = ("\"location\", \"filename\" or \"file\" key missing");
		}
		if ($phpContentType !== "application/json") {
			$error = ("\"content-type\" header missing, or not \"application/json\"");
		}
		if ($error) {
			$stdClass->result = returnError($error);
			return $stdClass;
		}
		$realLocation = realpath($configFileLocation.$phpLocation)."/";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $phpFile);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($curl);
		curl_close($curl);
		if (file_exists($realLocation.$phpFilename) && $phpOverwrite === "no") {
			$phpFilename = time().".".$phpFilename;
			while (file_exists($realLocation.$phpFilename)) {
				$phpFilename = substr(md5(microtime()),rand(0,26),5).time().".".$phpFilename;
			}
		}
		if ($file = fopen($realLocation.$phpFilename, "w")) {
			fwrite($file, $result);
			fclose($file);
			$stdClass->result = returnSuccess($phpFilename);
		} else {
			$stdClass->result = returnError("File write failed");
		}
		return $stdClass;
	}

	public function activate() {
	}
	
	public function deactivate() {
	}
}
