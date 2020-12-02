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
		$lang->load("api");
		$api = APISystem::get_instance();
		include "inc/plugins/restfulapi/functions/filefunctions.php";
		include "inc/plugins/restfulapi/functions/varfunctions.php";
		include "inc/plugins/restfulapi/functions/stringfunctions.php";
		$apiKeyProperties = array(
			"delete" => array("location", 1, "json"),
			"directorylist" => array("location", 1, "json"),
			"download" => array("location, filename, file", 1, "json"),
			"makedirectory" => array("location", 0, "json"),
			"read" => array("location", 1, "json"),
			"upload" => array("location, filename", 1, "post"),
			"write" => array("location, filename, content", 1, "json")
		);
		$phpContentType = $_SERVER["CONTENT_TYPE"];
		$configFileLocation = $mybb->settings["apifilelocation"];
		$stdClass = new stdClass();
		$phpData = array();
		$urlAction = $api->paths[1];
		if (checkIfKeySetAndInArray($urlAction, $apiKeyProperties)) {
			$apiDataMethod = $apiKeyProperties[$urlAction][2];
			switch ($apiDataMethod) {
				case "json":
					$rawBody = file_get_contents("php://input");
					if ($phpContentType !== "application/json") {
						throw new BadRequestException($lang->api_incorrect_content_type."\"application/json\"");
					}
				break;
				case "post":
					$rawBody = $_POST["json"];
					if (!strpos($phpContentType, "multipart/form-data") === 0) {
						throw new BadRequestException($lang->api_incorrect_content_type."\"multipart/form-data\"");
					}
				break;
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
			$apiRequiredKeys = explode(", ", $apiKeyProperties[$urlAction][0]);
			foreach ($apiRequiredKeys as $key) {
				if (!checkIfSetAndString($phpData[$key], $key)) {
					throw new BadRequestException($lang->api_key_missing.$apiKeyProperties[$urlAction][0]);
				}
			}
			$apiNeedsTraversalCheck = $apiKeyProperties[$urlAction][1];
			if ($apiNeedsTraversalCheck === true) {
				if (!checkIfTraversal($configFileLocation.$phpData["location"], $configFileLocation)) {
					throw new BadRequestException($lang->api_directory_traversal_failed);
				}
			}
		} else {
			throw new BadRequestException($lang->api_no_valid_action_specified);
		}
		switch(strtolower($urlAction)) {
			case "delete":
				$realLocation = realpath($configFileLocation.$phpData["location"]);
				if (is_dir($realLocation)) {
					if (rmdir($realLocation)) {
						$stdClass->location = $phpData["location"];
					} else {
						throw new BadRequestException($lang->api_directory_write_failed);
					}
				} else {
					if (unlink($realLocation)) {
						$stdClass->location = $phpData["location"];
					} else {
						throw new BadRequestException($lang->api_file_write_failed);
					}
				}
			break;
			case "directorylist":
				if (is_dir($configFileLocation.$phpData["location"])) {
					$stdClass->location = $phpData["location"];
					$stdClass->contents = scandir($configFileLocation.$phpData["location"]);
				} else {
					throw new BadRequestException($lang->api_directory_is_file);
				}
			break;
			case "download":
				if (!checkIfFilenameDirectory(dirname($configFileLocation.$phpData["location"].$phpData["filename"]), $configFileLocation.$phpData["location"])) {
					throw new BadRequestException($lang->api_key_contains_directory."\"filename\"");
				}
				$realLocation = realpath($configFileLocation.$phpData["location"])."/";
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $phpData["file"]);
				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($curl);
				curl_close($curl);
				$phpData["filename"] = checkFileRename($realLocation, $phpData["filename"], $phpData["overwrite"]);
				if ($file = fopen($realLocation.$phpData["filename"], "w")) {
					fwrite($file, $result);
					fclose($file);
					$stdClass->location = $phpData["location"];
					$stdClass->filename = $phpData["filename"];
				} else {
					throw new BadRequestException($lang->api_file_write_failed);
				}
			break;
			case "makedirectory":
				if (!checkIfTraversal(dirname($configFileLocation.$phpData["location"]), $configFileLocation)) {
					throw new BadRequestException($lang->api_directory_traversal_failed);
				}
				if (file_exists($configFileLocation.$phpData["location"])) {
					throw new BadRequestException($lang->api_file_or_directory_exists);
				}
				if (mkdir($configFileLocation.$phpData["location"])) {
					$stdClass->location = $phpData["location"];
				} else {
					throw new BadRequestException($lang->api_directory_write_failed);
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
					$stdClass->location = $phpData["location"];
				} else {
					throw new BadRequestException($lang->api_file_read_failed);
				}
			break;
			case "upload":
				if (!checkIfFilenameDirectory(dirname($configFileLocation.$phpData["location"].$phpData["filename"]), $configFileLocation.$phpData["location"])) {
					throw new BadRequestException($lang->api_key_contains_directory."\"filename\"");
				}
				if (!isset($_FILES['file'])) {
					throw new BadRequestException($lang->api_file_missing."\"file\"");
				}
				$realLocation = realpath($configFileLocation.$phpData["location"])."/";
				$phpData["filename"] = checkFileRename($realLocation, $phpData["filename"], $phpData["overwrite"]);
				if (move_uploaded_file($phpFile, $realLocation.$phpData["filename"])) {
					$stdClass->location = $phpData["location"];
					$stdClass->filename = $phpData["filename"];
				} else {
					throw new BadRequestException($lang->api_file_write_failed);
				}
			break;
			case "write":
				$realLocation = realpath($configFileLocation.$phpData["location"])."/";
				if (is_dir($realLocation)) {
					throw new BadRequestException($lang->api_file_is_directory);
				}
				$phpData["filename"] = checkFileRename($realLocation, $phpData["filename"], $phpData["overwrite"]);
				if ($phpData["append"] === "yes") {
					$writeMode = "a";
				} else {
					$writeMode = "w";
				}
				if ($file = fopen($realLocation.$phpData["filename"], $writeMode)) {
					fwrite($file, $phpData["content"]);
					fclose($file);
					$stdClass->content = $phpData["content"];
					$stdClass->location = $phpData["location"];
					$stdClass->filename = $phpData["filename"];
				} else {
					throw new BadRequestException($lang->api_file_write_failed);
				}
			break;
		}
		return $stdClass;
	}

	public function activate() {
	}
	
	public function deactivate() {
	}
}
