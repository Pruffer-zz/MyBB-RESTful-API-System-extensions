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
		include "inc/plugins/restfulapi/functions/jsonfunctions.php";
		include "inc/plugins/restfulapi/functions/stringfunctions.php";
		$configFileLocation = $mybb->settings["apifilelocation"];
		$stdClass = new stdClass();
		$rawBody = $_POST["json"];
		if (!($body = checkIfJson($rawBody))) {
			throw new BadRequestException("Invalid JSON data.");
		}
		$phpLocation = getKeyValue("location", $body);
		$phpOverwrite = getKeyValue("overwrite", $body);
		$phpFilename = getKeyValue("filename", $body);
		$phpFile = $_FILES['file']['tmp_name'];
		$phpContentType = $_SERVER["CONTENT_TYPE"];
		if (!checkIfTraversal($configFileLocation.$phpLocation, $configFileLocation)) {
			$error = ("Directory traversal check failed, or location doesn't exist.");
		}
		if (!checkIfFilenameDirectory(dirname($configFileLocation.$phpLocation.$phpFilename), $configFileLocation.$phpLocation)) {
			$error = ("\"filename\" key contains a directory.");
		}
		if (!checkIfSetAndString($phpLocation) || !checkIfSetAndString($phpFilename)) {
			$error = ("\"location\" or \"filename\" key missing.");
		}
		if (!isset($_FILES['file'])) {
			$error = ("\"file\" file missing.");
		}
		if (!strpos($phpContentType, "multipart/form-data") === 0) {
			$error = ("\"content-type\" header missing, or doesn't start with \"multipart/form-data\".");
		}
		if ($error) {
			throw new BadRequestException($error);
		}
		$realLocation = realpath($configFileLocation.$phpLocation)."/";
		if (file_exists($realLocation.$phpFilename) && $phpOverwrite === "no") {
			$phpFilename = time().".".$phpFilename;
			while (file_exists($realLocation.$phpFilename)) {
				$phpFilename = substr(md5(microtime()),rand(0,26),5).time().".".$phpFilename;
			}
		}
		if (move_uploaded_file($phpFile, $realLocation.$phpFilename)) {
			$stdClass->result = returnSuccess($phpFilename);
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
