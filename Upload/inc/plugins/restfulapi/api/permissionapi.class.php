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
class PermissionAPI extends RESTfulAPI {
	
	public function info() {
		return array(
			"name" => "Permission",
			"description" => "This API exposes permission interface.",
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
		if(checkIfSetAndString($phpAction)) {
			switch(strtolower($phpAction)) {
				case "moderation" :
					if(checkIfSetAndNumerical($phpForumId)) {
						$fid = $db->escape_string($phpForumId);
						return (object) forum_permissions($fid, $this->get_user()->uid);
					}
					else {
						return (object) forum_permissions(0, $this->get_user()->uid);
					}
				break;
			}
		} else {
			return (object) user_permissions($this->get_user()->uid);
		}
	}
	public function requires_auth() {
		return true;
	}
	private function usergroup_add_tags(&$array) {
		global $cache;
		$usergroups = $cache->read("usergroups");
		foreach($array as $group_id => $forum_permission) {
			$forum_permission["usergroup"] = $usergroups[$group_id];
			$array[$group_id] = $forum_permission;
		}
		return $array;
	}
}
