<?php

# This file is a part of MyBB RESTful API System plugin - version 0.2
# Released under the MIT Licence by medbenji (TheGarfield)
# Extensions released by Prüffer (avantheim.org) under GNU GPLv3

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
		global $mybb, $db, $lang;
		$api = APISystem::get_instance();
		include "inc/plugins/restfulapi/functions/filefunctions.php";
		include "inc/plugins/restfulapi/functions/varfunctions.php";
		include "inc/plugins/restfulapi/functions/stringfunctions.php";
		$configFileLocation = $mybb->settings["apifilelocation"];
		$stdClass = new stdClass();
		$phpData = array();
		if (!checkIfSetAndString($api->paths[1])) {
			throw new BadRequestException($lang->api_no_valid_action_specified);
		}
		$urlAction = $api->paths[1];
		$phpContentType = $_SERVER["CONTENT_TYPE"];
		if ($urlAction !== "upload") {
			$rawBody = file_get_contents("php://input");
			if ($phpContentType !== "application/json") {
				throw new BadRequestException($lang->api_incorrect_content_type."\"application/json\"");
			}
		} else {
			$rawBody = $_POST["json"];
			if (!strpos($phpContentType, "multipart/form-data") === 0) {
				throw new BadRequestException($lang->api_incorrect_content_type."\"multipart/form-data\"");
			}
		}
		if (!($body = checkIfJson($rawBody))) {
			throw new BadRequestException($lang->api_json_invalid);
		}
		try {
			foreach($body as $key=>$data) {
				$phpData[$key] = $data;
			}
		}
		catch (Exception $e) {
			throw new BadRequestException($lang->api_json_read_error);
		}
		if (!checkIfTraversal($configFileLocation.$phpData["location"], $configFileLocation)) {
			throw new BadRequestException($lang->api_directory_traversal_failed);
		}
		$locationOnlyApis = array(
			"directorylist",
			"delete",
			"read",
			"makedirectory"
		);
		if (!checkIfSetAndString($phpData["location"])) {
			if (checkIfSetAndInArray($urlAction, $locationOnlyApis)) {
				throw new BadRequestException($lang->api_key_missing."\"location\"");
			}
		}
		if (!checkIfTraversal($configFileLocation.$phpData["location"], $configFileLocation)) {
			throw new BadRequestException($lang->api_directory_traversal_failed);
		}
		switch(strtolower($urlAction)) {
			case "directorylist":
				if (is_dir($configFileLocation.$phpData["location"])) {
					$stdClass->contents = scandir($configFileLocation.$phpData["location"]);
					$stdClass->result = returnSuccess($phpData["location"]);
				} else {
					throw new BadRequestException($lang->api_directory_is_file);
				}
			break;
			case "upload":
				if (!checkIfFilenameDirectory(dirname($configFileLocation.$phpData["location"].$phpData["filename"]), $configFileLocation.$phpData["location"])) {
					throw new BadRequestException($lang->api_key_contains_directory."\"filename\"");
				}
				if (!checkIfSetAndString($phpData["location"]) || !checkIfSetAndString($phpData["filename"])) {
					throw new BadRequestException($lang->api_key_missing."\"location\", \"filename\"");
				}
				if (!isset($_FILES['file'])) {
					throw new BadRequestException($lang->api_file_missing."\"file\"");
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
					throw new BadRequestException($lang->api_file_write_failed);
				}
			break;
			case "delete":
				$realLocation = realpath($configFileLocation.$phpData["location"]);
				if (is_dir($realLocation)) {
					if (rmdir($realLocation)) {
						$stdClass->result = returnSuccess($phpData["location"]);
					} else {
						throw new BadRequestException($lang->api_directory_write_failed);
					}
				} else {
					if (unlink($realLocation)) {
						$stdClass->result = returnSuccess($phpData["location"]);
					} else {
						throw new BadRequestException($lang->api_file_write_failed);
					}
				}
			break;
			case "download":
				if (!checkIfFilenameDirectory(dirname($configFileLocation.$phpData["location"].$phpData["filename"]), $configFileLocation.$phpData["location"])) {
					throw new BadRequestException($lang->api_key_contains_directory."\"filename\"");
				}
				if (!checkIfSetAndString($phpData["location"]) || !checkIfSetAndString($phpData["filename"]) || !checkIfSetAndString($phpData["file"])) {
					throw new BadRequestException($lang->api_key_missing."\"location\", \"filename\", \"file\"");
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
					throw new BadRequestException($lang->api_file_write_failed);
				}
			break;
			case "read":
				$realLocation = realpath($configFileLocation.$phpData["location"]);
				if (is_dir($realLocation)) {
					throw new BadRequestException($lang->api_file_is_directory);
				}
				if ($file = fopen($realLocation, "r")) {
					$stdClass->contents = fread($file, filesize($realLocation));
					fclose($file);
					$stdClass->result = returnSuccess($phpData["location"]);
				} else {
					throw new BadRequestException($lang->api_file_read_failed);
				}
			break;
			case "write":
				if (!checkIfSetAndString($phpData["location"]) || !checkIfSetAndString($phpData["content"]) || !checkIfSetAndString($phpData["filename"])) {
					throw new BadRequestException($lang->api_key_missing."\"location\", \"content\", \"filename\"");
				}
				$realLocation = realpath($configFileLocation.$phpData["location"])."/";
				if (is_dir($realLocation)) {
					throw new BadRequestException($lang->api_file_is_directory);
				}
				if (file_exists($realLocation.$phpData["filename"]) && $phpData["overwrite"] === "no") {
					$phpData["filename"] = time().".".$phpData["filename"];
					while (file_exists($realLocation.$phpData["filename"])) {
						$phpData["filename"] = substr(md5(microtime()),rand(0,26),5).time().".".$phpData["filename"];
					}
				}
				if ($phpData["append"] === "yes") {
					$writeMode = "a";
				} else {
					$writeMode = "w";
				}
				if ($file = fopen($realLocation.$phpData["filename"], $writeMode)) {
					fwrite($file, $phpData["content"]);
					fclose($file);
					$stdClass->content = $phpData["content"];
				} else {
					throw new BadRequestException($lang->api_file_write_failed);
				}
			break;
			case "makedirectory":
				if (file_exists($configFileLocation.$phpData["location"])) {
					throw new BadRequestException($lang->api_file_or_directory_exists);
				}
				if (mkdir($configFileLocation.$phpData["location"])) {
					$stdClass->result = returnSuccess($phpData["location"]);
				} else {
					throw new BadRequestException($lang->api_directory_write_failed);
				}
			break;
			default:
				throw new BadRequestException($lang->api_no_valid_action_specified);
			break;
		}
		return $stdClass;
	}

	public function activate() {
	}
	
	public function deactivate() {
	}
}
