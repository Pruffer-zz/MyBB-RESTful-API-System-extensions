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
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/jsonfunctions.php";
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/varfunctions.php";
		$stdClass = new stdClass();
		$rawBody = file_get_contents("php://input");
		if (!($body = checkIfJson($rawBody))) {
			throw new BadRequestException("Invalid JSON data.");
		}
		$phpAction = getKeyValue("action", $body);
		$phpForumId = getKeyValue("forumid", $body);
		$phpContentType = $_SERVER["CONTENT_TYPE"];
		if ($phpContentType !== "application/json") {
			$error = ("\"content-type\" header missing, or not \"application/json\".");
		}
		if(!checkIfSetAndString($phpAction)) {
			$error = ("\"action\" key missing.");
		}
		if(checkIfSetAndString($phpForumId)) {
			$query = $db->simple_select('forums', 'fid', 'fid=\''.$phpForumId.'\'');
			$queryResult = $db->fetch_array($query);
			if (!$queryResult) {
				$error = ("Forum ID doesn't exist.");
			}
		}
		if ($error) {
			throw new BadRequestException($error);
		}
		$forums = cache_forums();
		switch (strtolower($phpAction)) {
			case "list" :
				if(checkIfSetAndString($phpForumId)) {
					return (object) $forums[$phpForumId];
				}
				else {
					return (object) $forums;
				}
			break;
			case "threads" :
				if(checkIfSetAndString($phpForumId)) {
					$threads = array();
					$fid = $db->escape_string($phpForumId);
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
				if(checkIfSetAndString($phpForumId) && $this->is_authenticated()) {
					return (object) forum_permissions($phpForumId, $this->get_user()->id, $this->get_user()->usergroup);
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
