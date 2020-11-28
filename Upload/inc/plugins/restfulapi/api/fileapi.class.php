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
class FileAPI extends RESTfulAPI {
	public function info() {
		return array(
			"name" => "File operation",
			"description" => "This API allows users to do operations with files, in a location specified in the \"API file location\" setting.",
			"default" => "deactivated"
		);
	}
	/**
	This is where you perform the action when the API is called, the parameter given is an instance of stdClass, this method should return an instance of stdClass.
	*/
	public function action() {
		global $mybb, $db;
		$api = APISystem::get_instance();
		include "inc/plugins/restfulapi/functions/filefunctions.php";
		include "inc/plugins/restfulapi/functions/varfunctions.php";
		include "inc/plugins/restfulapi/functions/stringfunctions.php";
		$configFileLocation = $mybb->settings["apifilelocation"];
		$stdClass = new stdClass();
		$phpData = array();
		if (!checkIfSetAndString($api->paths[1])) {
			throw new BadRequestException("No action specified in the URL.");
		}
		$urlAction = $api->paths[1];
		if ($urlAction !== "upload") {
			$rawBody = file_get_contents("php://input");
		} else {
			$rawBody = $_POST["json"];
		}
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
		switch(strtolower($urlAction)) {
			case "directorylist":
				if (!checkIfTraversal($configFileLocation.$phpData["location"], $configFileLocation)) {
					throw new BadRequestException("Directory traversal check failed, or location doesn't exist.");
				}
				if (!checkIfSetAndString($phpData["location"])) {
					throw new BadRequestException("\"location\" key missing.");
				}
				if ($phpContentType !== "application/json") {
					throw new BadRequestException("\"content-type\" header missing, or not \"application/json\".");
				}
				if (is_dir($configFileLocation.$phpData["location"])) {
					$stdClass->contents = scandir($configFileLocation.$phpData["location"]);
					$stdClass->result = returnSuccess($phpData["location"]);
				} else {
					throw new BadRequestException("Specified directory is a file.");
				}
			break;
			case "upload":
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
			break;
			case "delete":
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
			break;
			case "download":
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
			break;
			case "read":
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
					throw new BadRequestException("Specified file is a directory.");
				}
				if ($file = fopen($realLocation, "r")) {
					$stdClass->contents = fread($file, filesize($realLocation));
					fclose($file);
					$stdClass->result = returnSuccess($phpData["location"]);
				} else {
					throw new BadRequestException("File read failed.");
				}
			break;
			case "write":
				if (!checkIfTraversal($configFileLocation.$phpData["location"], $configFileLocation)) {
					throw new BadRequestException("Directory traversal check failed, or location doesn't exist.");
				}
				if (!checkIfSetAndString($phpData["location"]) || !checkIfSetAndString($phpData["content"]) || !checkIfSetAndString($phpData["location"])) {
					throw new BadRequestException("\"location\" key missing.");
				}
				if ($phpContentType !== "application/json") {
					throw new BadRequestException("\"content-type\" header missing, or not \"application/json\".");
				}
				$realLocation = realpath($configFileLocation.$phpData["location"])."/";
				if (is_dir($realLocation)) {
					throw new BadRequestException("Specified file is a directory.");
				}
				if (file_exists($realLocation.$phpData["location"]) && $phpData["overwrite"] === "no") {
					$phpData["location"] = time().".".$phpData["location"];
					while (file_exists($realLocation.$phpData["location"])) {
						$phpData["location"] = substr(md5(microtime()),rand(0,26),5).time().".".$phpData["location"];
					}
				}
				if ($phpData["append"] === "yes") {
					$writeMode = "a";
				} else {
					$writeMode = "w";
				}
				if ($file = fopen($realLocation.$phpData["location"], $writeMode)) {
					fwrite($file, $phpData["content"]);
					fclose($file);
					$stdClass->content = $phpData["content"];
					$stdClass->result = returnSuccess($phpData["location"]);
				} else {
					throw new BadRequestException("File write failed.");
				}
			break;
			case "makedirectory":
				if (!checkIfTraversal(dirname($configFileLocation.$phpData["location"]), $configFileLocation)) {
					throw new BadRequestException("Directory traversal check failed, or parent directory doesn't exist.");
				}
				if (!checkIfSetAndString($phpData["location"])) {
					throw new BadRequestException("\"location\" key missing.");
				}
				if ($phpContentType !== "application/json") {
					throw new BadRequestException("\"content-type\" header missing, or not \"application/json\".");
				}
				if (file_exists($configFileLocation.$phpData["location"])) {
					throw new BadRequestException("Directory / file already exists.");
				}
				if (mkdir($configFileLocation.$phpData["location"])) {
					$stdClass->result = returnSuccess($phpData["location"]);
				} else {
					throw new BadRequestException("Directory creation failed.");
				}
			break;
			default:
				throw new BadRequestException("No valid option specified in the URL.");
			break;
		}
		return $stdClass;
	}

	public function activate() {
	}
	
	public function deactivate() {
	}
}
