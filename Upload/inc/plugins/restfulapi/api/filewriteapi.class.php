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
class FileWriteAPI extends RESTfulAPI {
	
	public function info() {
		return array(
			"name" => "File write",
			"description" => "This API allows users to write files to a location specified in filewriteapi.class.php.",
			"default" => "deactivated"
		);
	}
	/**
	This is where you perform the action when the API is called, the parameter given is an instance of stdClass, this method should return an instance of stdClass.
	*/
	public function action() {
		global $mybb, $db;
		function checkIfJson($body) {
			if ($return = json_decode($body)) {
				return $return;
			} else {
				return false;
			}
		}
		function getKeyValue($key, $body) {
			if ($returnKey = $body->$key) {
				return $returnKey;
			} else {
				return false;
			}
		}
		function returnError($message) {
			return "Unsuccessful: ".$message;
		}
		function returnSuccess($message) {
			return "Successful: ".$message;
		}
		function checkIfTraversal($path, $location) {
			$realPath = realpath($path);
			$realLocation = realpath($location);
			if ($realPath === false || strpos($realPath, $realLocation) !== 0) {
				return false;
			} else {
				return true;
			}
		}
		function checkIfSetAndString($var) {
			if (isset($var) && is_string($var)) {
				return true;
			} else {
				return false;
			}
		}
		$stdClass = new stdClass();
		$rawBody = file_get_contents("php://input");
		if (!($body = checkIfJson($rawBody))) {
			$stdClass->result = returnError("Invalid JSON data");
			return $stdClass;
		}
		$phpContent = getKeyValue("content", $body);
		$phpLocation = getKeyValue("location", $body);
		$phpAppend = getKeyValue("append", $body);
		$phpOverwrite = getKeyValue("overwrite", $body);
		$phpFilename = getKeyValue("filename", $body);
		$phpContentType = $_SERVER["CONTENT_TYPE"];
		$location = "/path/to/fun/files/"; // Make sure to change this part, and include a trailing slash
		if (!checkIfTraversal($location.$phpLocation, $location)) {
			$error = ("Directory traversal check failed, or location doesn't exist");
		}
		if (!checkIfSetAndString($phpLocation) || !checkIfSetAndString($phpContent) || !checkIfSetAndString($phpFilename)) {
			$error = ("\"location\" key missing");
		}
		if ($phpContentType !== "application/json") {
			$error = ("\"content-type\" header missing, or not \"application/json\"");
		}
		if ($error) {
			$stdClass->result = returnError($error);
			return $stdClass;
		}
		$realLocation = realpath($location.$phpLocation)."/";
		if (is_dir($realLocation)) {
			$error = ("Specified file is a directory");
		}
		if (file_exists($realLocation.$phpFilename) && $phpOverwrite === "no") {
			$phpFilename = time().".".$phpFilename;
			while (file_exists($realLocation.$phpFilename)) {
				$phpFilename = substr(md5(microtime()),rand(0,26),5).time().".".$phpFilename;
			}
		}
		if ($phpAppend === "yes") {
			$writeMode = "a";
		} else {
			$writeMode = "w";
		}
		if ($file = fopen($realLocation.$phpFilename, $writeMode)) {
			fwrite($file, $body->content);
            fclose($file);
			$stdClass->data = $body;
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
