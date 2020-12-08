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
			"description" => "This API exposes the permission interface.",
			"default" => "activated"
		);
	}
	/**
	This is where you perform the action when the API is called, the parameter given is an instance of stdClass, this method should return an instance of stdClass.
	*/
	public function action() {
		global $mybb, $db, $lang;
		$lang->load("api");
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/errorfunctions.php";
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/jsoncheckfunctions.php";
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/varfunctions.php";
		$stdClass = new stdClass();
		$phpData = jsonPrecheckAndBodyToArray(file_get_contents("php://input"), "json", $_SERVER["CONTENT_TYPE"], array("forumid"));
		$query = $db->simple_select('forums', 'fid', 'fid=\''.$phpData["forumid"].'\'');
		$queryResult = $db->fetch_array($query);
		if (!$queryResult) {
			throwBadRequestException($lang->api_id_does_not_exist);
		}
		if(checkIfSetAndString($phpData["action"])) {
			switch(strtolower($phpData["action"])) {
				case "moderation":
					if(checkIfSetAndNumerical($phpData["forumid"])) {
						$fid = $db->escape_string($phpData["forumid"]);
						return (object) forum_permissions($fid, $this->get_user()->uid);
					} else {
						return (object) forum_permissions(0, $this->get_user()->uid);
					}
				break;
				default:
					throwBadRequestException($lang->api_no_valid_action_specified);
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
