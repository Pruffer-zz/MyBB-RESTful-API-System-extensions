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
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/filefunctions.php";
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/varfunctions.php";
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/jsoncheckfunctions.php";
		$apiKeyProperties = array(
			"delete" => array(array("location"), true, "json", false),
			"directorylist" => array(array("location"), true, "json", false),
			"download" => array(array("location", "filename", "file"), true, "json", array("location", array("filename"))),
			"makedirectory" => array(array("location"), false, "json", false),
			"read" => array(array("location"), true, "json", false),
			"rename" => array(array("location", "filename-old", "filename-new"), true, "json", array("location", array("filename-old", "filename-new"))),
			"upload" => array(array("location", "filename"), true, "post", array("location", array("filename"))),
			"write" => array(array("location", "filename", "content"), true, "json", array("location", array("filename")))
		);
		$phpContentType = $_SERVER["CONTENT_TYPE"];
		$configFileLocation = $mybb->settings["apifilelocation"];
		$stdClass = new stdClass();
		$urlAction = $api->paths[1];
		if (checkIfKeySetAndInArray($urlAction, $apiKeyProperties)) {
			$phpData = jsonPrecheckAndBodyToArray(file_get_contents("php://input"), $apiKeyProperties[$urlAction][2], $_SERVER["CONTENT_TYPE"], $apiKeyProperties[$urlAction][0]);
			$apiNeedsTraversalCheck = $apiKeyProperties[$urlAction][1];
			if ($apiNeedsTraversalCheck === true) {
				if (!checkIfTraversal($configFileLocation.$phpData["location"], $configFileLocation)) {
					throw new BadRequestException($lang->api_directory_traversal_failed);
				}
			}
			$apiNeedsFilenameDirectoryCheck = $apiKeyProperties[$urlAction][3];
			if ($apiNeedsFilenameDirectoryCheck !== false) {
				$location = $phpData[$apiNeedsFilenameDirectoryCheck[0]];
				foreach ($apiNeedsFilenameDirectoryCheck[1] as $key) {
					if (!checkIfFilenameDirectory(dirname(realpath($configFileLocation.$location)."/".$phpData[$key]), realpath($configFileLocation.$location))) {
						throw new BadRequestException($lang->api_key_contains_directory."\"".$key."\"");
					}
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
				$realLocation = realpath($configFileLocation.$phpData["location"]);
				if (!checkIfTraversal(dirname($realLocation), $configFileLocation)) {
					throw new BadRequestException($lang->api_directory_traversal_failed);
				}
				if (file_exists($realLocation)) {
					throw new BadRequestException($lang->api_file_or_directory_exists);
				}
				if (mkdir($realLocation)) {
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
			case "rename":
				$realLocation = realpath($configFileLocation.$phpData["location"])."/";
				$phpData["filename-new"] = checkFileRename($realLocation.$phpData["filename-old"], $phpData["filename-new"], $phpData["overwrite"]);
				if (!file_exists($realLocation.$phpData["filename-old"])) {
					throw new BadRequestException($lang->api_file_or_directory_does_not_exist);
				}
				if (rename($realLocation.$phpData["filename-old"], $realLocation.$phpData["filename-new"])) {
					$stdClass->location = $phpData["location"];
					$stdClass->filenameNew = $phpData["filename-new"];
				} else {
					throw new BadRequestException($lang->api_file_write_failed);
				}
			break;
			case "upload":
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
