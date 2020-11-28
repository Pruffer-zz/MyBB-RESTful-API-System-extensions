<?php

# This file is a part of MyBB RESTful API System plugin - version 0.2
# Released under the MIT Licence by medbenji (TheGarfield)
# Modified by Prüffer (avantheim.org)

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

/**
This interface should be implemented by APIs, see VersionAPI for a simple example.
*/
class ForumAPI extends RESTfulAPI {
	
	public function info() {
		return array(
			"name" => "Date",
			"description" => "This API exposes the forums and categories present in the board.",
			"default" => "activated"
		);
	}
	/**
	This is where you perform the action when the API is called, the parameter given is an instance of stdClass, this method should return an instance of stdClass.
	*/
	public function action() {
		global $mybb, $db;
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/varfunctions.php";
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
		if ($phpContentType !== "application/json") {
			throw new BadRequestException("\"content-type\" header missing, or not \"application/json\".");
		}
		if(!checkIfSetAndString($phpData["action"])) {
			throw new BadRequestException("\"action\" key missing.");
		}
		if(checkIfSetAndString($phpData["forumid"])) {
			$query = $db->simple_select('forums', 'fid', 'fid=\''.$phpData["forumid"].'\'');
			$queryResult = $db->fetch_array($query);
			if (!$queryResult) {
				throw new BadRequestException("Forum ID doesn't exist.");
			}
		}
		$forums = cache_forums();
		switch (strtolower($phpData["action"])) {
			case "list" :
				if(checkIfSetAndString($phpData["forumid"])) {
					return (object) $forums[$phpData["forumid"]];
				}
				else {
					return (object) $forums;
				}
			break;
			case "threads" :
				if(checkIfSetAndString($phpData["forumid"])) {
					$threads = array();
					$fid = $db->escape_string($phpData["forumid"]);
					$query = $db->write_query("SELECT * FROM ".TABLE_PREFIX."threads t WHERE t.`fid` = '{$fid}'");
					while($thread = $db->fetch_array($query)) {
						$threads[$thread["tid"]] = $thread;
					}
					return (object) $threads;
				}
				else {
					throw new BadRequestException("Unable to access specified forum ID.");
				}
			break;
			case "permissions" :
				if(checkIfSetAndString($phpData["forumid"]) && $this->is_authenticated()) {
					return (object) forum_permissions($phpData["forumid"], $this->get_user()->id, $this->get_user()->usergroup);
				}
				else {
					throw new BadRequestException("Unable to access specified forum ID.");
				}
			default:
			break;
		}
		throw new BadRequestException("No valid option given in the URL.");
	}
}
