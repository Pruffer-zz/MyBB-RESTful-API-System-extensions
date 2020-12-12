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
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/errorfunctions.php";
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/filefunctions.php";
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/jsoncheckfunctions.php";
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/varfunctions.php";
		$apiKeyProperties = array(
			"copy" => array(
				array("location-old", "location-new", "filename"),
				array("overwrite", "move"),
				true,
				"json",
				array(
					array("location-new", "location-old"),
					array("filename")
				)
			),
			"delete" => array(
				array("location"),
				false,
				true,
				"json",
				false
			),
			"directorylist" => array(
				array("location"),
				false,
				true,
				"json",
				false
			),
			"download" => array(
				array("location", "filename", "file"),
				array("overwrite"),
				true, "json",
				array(
					array("location"),
					array("filename")
				)
			),
			"hash" => array(
				array("location", "algorithm"),
				false,
				true,
				"json",
				false
			),
			"makedirectory" => array(
				array("location"),
				false,
				false,
				"json",
				false
			),
			"read" => array(
				array("location"),
				array("overwrite"),
				true,
				"json",
				false
			),
			"rename" => array(
				array("location", "filename-old", "filename-new"),
				array("overwrite"),
				true,
				"json",
				array(
					array("location"),
					array("filename-old", "filename-new")
				)
			),
			"upload" => array(
				array("location", "filename"),
				array("overwrite"),
				true,
				"post",
				array(
					array("location"),
					array("filename")
				)
			),
			"url" => array(
				array("location"),
				false,
				true,
				"json",
				false
			),
			"write" => array(
				array("location", "filename", "content"),
				array("overwrite", "append"),
				true,
				"json",
				array(
					array("location"),
					array("filename")
				)
			)
		);
		$phpContentType = $_SERVER["CONTENT_TYPE"];
		$configFileLocation = $mybb->settings["apifilelocation"];
		if ($configFileLocation === "") {
			throwBadRequestException($lang->api_settings_disabled);
		}
		$stdClass = new stdClass();
		$urlAction = $api->paths[1];
		if (checkIfKeySetAndInArray($urlAction, $apiKeyProperties)) {
			if ($apiKeyProperties[$urlAction][1] === false) {
				$requiredBoolKeys = array();
			} else {
				$requiredBoolKeys = $apiKeyProperties[$urlAction][1];
			}
			$phpData = jsonPrecheckAndBodyToArray(file_get_contents("php://input"), $apiKeyProperties[$urlAction][3], $_SERVER["CONTENT_TYPE"], $apiKeyProperties[$urlAction][0], $requiredBoolKeys);
			$apiNeedsTraversalCheck = $apiKeyProperties[$urlAction][2];
			if ($apiNeedsTraversalCheck === true) {
				if (!checkIfTraversal($configFileLocation.$phpData["location"], $configFileLocation)) {
					throwBadRequestException($lang->api_directory_traversal_failed);
				}
			}
			$apiNeedsFilenameDirectoryCheck = $apiKeyProperties[$urlAction][3];
			if ($apiNeedsFilenameDirectoryCheck === true) {
				foreach ($apiNeedsFilenameDirectoryCheck[0] as $locationKey) {
					foreach ($apiNeedsFilenameDirectoryCheck[1] as $filenameKey) {
						if (!checkIfFilenameDirectory(dirname(realpath($configFileLocation.$locationKey)."/".$phpData[$key]), realpath($configFileLocation.$locationKey))) {
							throwBadRequestException($lang->api_key_contains_directory."\"".$key."\"");
						}
					}
				}
			}
		} else {
			throwBadRequestException($lang->api_no_valid_action_specified);
		}
		switch(strtolower($urlAction)) {
			case "copy":
				$realLocationOld = realpath($configFileLocation.$phpData["location-old"])."/";
				$realLocationNew = realpath($configFileLocation.$phpData["location-new"])."/";
				$phpData["filename"] = checkFileRename($realLocationNew.$phpData["filename"], $phpData["filename"], $phpData["overwrite"]);
				if (is_dir($realLocationOld.$phpData["filename"])) {
					throwBadRequestException($lang->api_file_is_directory);
				}
				if (!file_exists($realLocationOld.$phpData["filename"])) {
					throwBadRequestException($lang->api_file_does_not_exist);
				}
				try {
					if ($phpData["move"] === false) {
						copy($realLocationOld.$phpData["filename"], $realLocationNew.$phpData["filename"]);
					} else {
						rename($realLocationOld.$phpData["filename"], $realLocationNew.$phpData["filename"]);
					}
					$stdClass->locationNew = $phpData["location-new"];
					$stdClass->filename = $phpData["filename"];
				}
				catch (Exception $e) {
					throwBadRequestException($lang->api_file_write_failed);
				}
			break;
			case "delete":
				$realLocation = realpath($configFileLocation.$phpData["location"]);
				if (is_dir($realLocation)) {
					if (rmdir($realLocation)) {
						$stdClass->location = $phpData["location"];
					} else {
						throwBadRequestException($lang->api_directory_write_failed);
					}
				} else {
					if (unlink($realLocation)) {
						$stdClass->location = $phpData["location"];
					} else {
						throwBadRequestException($lang->api_file_write_failed);
					}
				}
			break;
			case "directorylist":
				if (is_dir($configFileLocation.$phpData["location"])) {
					$stdClass->location = $phpData["location"];
					$stdClass->contents = scandir($configFileLocation.$phpData["location"]);
				} else {
					throwBadRequestException($lang->api_directory_is_file);
				}
			break;
			case "download":
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
					throwBadRequestException($lang->api_file_write_failed);
				}
			break;
			case "hash":
				$realLocation = realpath($configFileLocation.$phpData["location"]);
				if (is_dir($realLocation)) {
					throwBadRequestException($lang->api_file_is_directory);
				}
				if ($file = fopen($realLocation, "r")) {
					if ($hash = hash_file($phpData["algorithm"], $realLocation)) {
						$stdClass->hash = $hash;
					} else {
						throwBadRequestException($lang->api_hash_error);
					}
				} else {
					throwBadRequestException($lang->api_file_read_failed);
				}
			break;
			case "makedirectory":
				$newLocation = $configFileLocation.$phpData["location"];
				if (!checkIfTraversal(dirname($newLocation), $configFileLocation)) {
					throwBadRequestException($lang->api_directory_traversal_failed);
				}
				if (file_exists($newLocation)) {
					throwBadRequestException($lang->api_file_or_directory_exists);
				}
				if (mkdir($newLocation)) {
					$stdClass->location = $phpData["location"];
				} else {
					throwBadRequestException($lang->api_directory_write_failed);
				}
			break;
			case "read":
				$realLocation = realpath($configFileLocation.$phpData["location"]);
				if (is_dir($realLocation)) {
					throwBadRequestException($lang->api_file_is_directory);
				}
				if ($file = fopen($realLocation, "r")) {
					$stdClass->contents = fread($file, filesize($realLocation));
					fclose($file);
					$stdClass->location = $phpData["location"];
				} else {
					throwBadRequestException($lang->api_file_read_failed);
				}
			break;
			case "rename":
				$realLocation = realpath($configFileLocation.$phpData["location"])."/";
				$phpData["filename-new"] = checkFileRename($realLocation.$phpData["filename-old"], $phpData["filename-new"], $phpData["overwrite"]);
				if (!file_exists($realLocation.$phpData["filename-old"])) {
					throwBadRequestException($lang->api_file_or_directory_does_not_exist);
				}
				if (rename($realLocation.$phpData["filename-old"], $realLocation.$phpData["filename-new"])) {
					$stdClass->location = $phpData["location"];
					$stdClass->filenameNew = $phpData["filename-new"];
				} else {
					throwBadRequestException($lang->api_file_write_failed);
				}
			break;
			case "upload":
				if (!isset($_FILES['file'])) {
					throwBadRequestException($lang->api_file_missing."\"file\"");
				}
				$realLocation = realpath($configFileLocation.$phpData["location"])."/";
				$phpData["filename"] = checkFileRename($realLocation, $phpData["filename"], $phpData["overwrite"]);
				if (move_uploaded_file($phpFile, $realLocation.$phpData["filename"])) {
					$stdClass->location = $phpData["location"];
					$stdClass->filename = $phpData["filename"];
				} else {
					throwBadRequestException($lang->api_file_write_failed);
				}
			break;
			case "url":
				$configBaseUrl = $mybb->settings["apifileurlbase"];
				if ($configBaseUrl === "") {
					throwBadRequestException($lang->api_settings_disabled);
				}
				$realLocation = realpath($configFileLocation.$phpData["location"]);
				if (!file_exists($realLocation)) {
					throwBadRequestException($lang->api_file_or_directory_does_not_exist);
				}
				$stdClass->url = $configBaseUrl.$phpData["location"];
			break;
			case "write":
				$realLocation = realpath($configFileLocation.$phpData["location"])."/";
				$phpData["filename"] = checkFileRename($realLocation, $phpData["filename"], $phpData["overwrite"]);
				if ($phpData["append"] === true) {
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
					throwBadRequestException($lang->api_file_write_failed);
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
