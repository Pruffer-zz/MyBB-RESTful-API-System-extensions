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
class CreateThreadAPI extends RESTfulAPI {
	
	public function info() {
		return array(
			"name" => "Create Thread",
			"description" => "This API exposes an API capable of creating threads.",
			"default" => "deactivated" // only activate it if needed
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
		require_once MYBB_ROOT . 'inc/functions_post.php';
		require_once MYBB_ROOT . '/inc/datahandlers/post.php';
		$stdClass = new stdClass();
		$phpData = jsonPrecheckAndBodyToArray(file_get_contents("php://input"), "json", $_SERVER["CONTENT_TYPE"], array("subject", "forumid", "message", "ipaddress"));
		$query = $db->simple_select('forums', 'fid', 'fid=\''.$phpData["forumid"].'\'');
		$queryResult = $db->fetch_array($query);
		if (!$queryResult) {
			throwBadRequestException($lang->api_id_does_not_exist);
		}
		$phpData["forumid"] = (int) $phpData["forumid"];
		$phpData["prefix"] = checkIfSetAndString($phpData["prefix"]) ? $phpData["prefix"] : null;
		$phpData["icon"] = checkIfSetAndString($phpData["icon"]) ? $phpData["icon"] : null;
		$phpData["savedraft"] = checkIfSetAndInArray($phpData["savedraft"], array("1", "0")) ? (int) $phpData["savedraft"] : 0;
		$phpData["subscriptionmethod"] = checkIfSetAndInArray($phpData["subscriptionmethod"], array("", "none", "instant")) ? $phpData["subscriptionmethod"] : "";
		$phpData["signature"] = checkIfSetAndInArray($phpData["signature"], array("1", "0")) ? (int) $phpData["signature"] : 0;
		$phpData["disablesmilies"] = checkIfSetAndInArray($phpData["disablesmilies"], array("1", "0")) ? (int) $phpData["disablesmilies"] : 0;
		$phpData["modclosethread"] = checkIfSetAndInArray($phpData["modclosethread"], array("1", "0")) ? (int) $phpData["modclosethread"] : 0;
		$phpData["modstickthread"] = checkIfSetAndInArray($phpData["modstickthread"], array("1", "0")) ? (int) $phpData["modstickthread"] : 0;
		$posthandler = new PostDataHandler('insert');
		$posthandler->action = 'thread';
		$data = array(
			"uid" => $this->get_user()->uid,
			"username" => $this->get_user()->username,
			"subject" => $phpData["subject"],
			"fid" => $phpData["forumid"],
			"prefix" => $phpData["prefix"],
			"message" => $phpData["message"],
			"ipaddress" => $phpData["ipaddress"],
			"icon" => $phpData["icon"],
			"savedraft" => $phpData["savedraft"],
			"options" => array(
				"subscriptionmethod" => $phpData["subscriptionmethod"],
				"signature" => $phpData["signature"],
				"disablesmilies" => $phpData["disablesmilies"],
			)
		);
		if(isset($this->get_user()->is_moderator) && $this->get_user()->is_moderator) {
			$data[] = array(
				"closethread" => $phpData["modclosethread"],
				"stickthread" => $phpData["modstickthread"]
			);
		}
		$posthandler->set_data($data);
		if (!$posthandler->validate_thread()) {
			throwBadRequestException((object) $posthandler->get_friendly_errors());
		}
		return (object) $posthandler->insert_thread();
	}
	/**
	* We need the user to be authenticated
	* @return boolean
	*/
	public function requires_auth() {
		return true;
	}
}
