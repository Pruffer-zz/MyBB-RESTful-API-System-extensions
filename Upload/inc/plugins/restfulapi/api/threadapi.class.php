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
class ThreadAPI extends RESTfulAPI {
	
	public function info() {
		return array(
			"name" => "Thread",
			"description" => "This API exposes threads and posts.",
			"default" => "activated"
		);
	}
	/**
	This is where you perform the action when the API is called, the parameter given is an instance of stdClass, this method should return an instance of stdClass.
	*/
	public function action() {
		global $mybb, $db;
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/varfunctions.php";
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/jsoncheckfunctions.php";
		$stdClass = new stdClass();
		$phpData = jsonPrecheckAndBodyToArray(file_get_contents("php://input"), "json", $_SERVER["CONTENT_TYPE"], array("action"));
		switch (strtolower($phpData["action"])) {
			case "list":
				if(checkIfSetAndString($phpData["threadid"]) && isset($forums[$phpData["threadid"]])) {
					return (object) $forums[$phpData["threadid"]];
				}
				else {
					throw new BadRequestException($lang->api_id_not_specified);
				}
			break;
			case "posts":
				if(checkIfSetAndString($phpData["threadid"])) {
					$posts = array();
					$tid = $db->escape_string($phpData["threadid"]);
					$query = $db->write_query("SELECT * FROM ".TABLE_PREFIX."posts p WHERE p.`tid` = '{$tid}'");
					while($post = $db->fetch_array($query)) {
						$posts[$post["pid"]] = $post;
					}
					return (object) $posts;
				} else {
					throw new BadRequestException($lang->api_id_not_specified);
				}
			break;
			case "permissions":
				$forumpermissions = forum_permissions();
				return (object) $forumpermissions;
			default:
                throw new BadRequestException($lang->api_no_valid_action_specified);
			break;
		}
	}
}
