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
		global $mybb, $db;
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/jsonfunctions.php";
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/varfunctions.php";
		require_once MYBB_ROOT . 'inc/functions_post.php';
		require_once MYBB_ROOT . '/inc/datahandlers/post.php';
		$stdClass = new stdClass();
		$rawBody = file_get_contents("php://input");
		if (!($body = checkIfJson($rawBody))) {
			throw new BadRequestException("Invalid JSON data.");
		}
		$phpSubject = getKeyValue("subject", $body);
		$phpForumId = getKeyValue("forumid", $body);
		$phpMessage = getKeyValue("message", $body);
		$phpIpAddress = getKeyValue("ipaddress", $body);
		$phpPrefix = getKeyValue("prefix", $body);
		$phpIcon = getKeyValue("icon", $body);
		$phpSaveDraft = getKeyValue("savedraft", $body);
		$phpSubscriptionMetod = getKeyValue("subscriptionmethod", $body);
		$phpSignature = getKeyValue("signature", $body);
		$phpDisableSmilies = getKeyValue("disablesmilies", $body);
		$phpModCloseThread = getKeyValue("modclosethread", $body);
		$phpModStickThread = getKeyValue("modstickthread", $body);
		$phpContentType = $_SERVER["CONTENT_TYPE"];
		if ($phpContentType !== "application/json") {
			$error = ("\"content-type\" header missing, or not \"application/json\"");
		}
		if(!checkIfSetAndString($phpSubject) || !checkIfSetAndNumerical($phpForumId) || !checkIfSetAndString($phpMessage) || !checkIfSetAndString($phpIpAddress)) {
			$error = ("\"subject\", \"id\", \"message\", or \"ipaddress\" keys missing or malformed");
		}
		$query = $db->simple_select('forums', 'fid', 'fid=\''.$phpForumId.'\'');
		$queryResult = $db->fetch_array($query);
		if (!$queryResult) {
			$error = ("Forum ID doesn't exist");
		}
		if ($error) {
			throw new BadRequestException($error);
		}
		$phpForumId = (int) $phpForumId;
		$phpPrefix = checkIfSetAndString($phpPrefix) ? $phpPrefix : null;
		$phpIcon = checkIfSetAndString($phpIcon) ? $phpIcon : null;
		$phpSaveDraft = checkIfSetAndInArray($phpSaveDraft, array("1", "0")) ? (int) $phpSaveDraft : 0;
		$phpSubscriptionMetod = checkIfSetAndInArray($phpSubscriptionMetod, array("", "none", "instant")) ? $phpSubscriptionMetod : "";
		$phpSignature = checkIfSetAndInArray($phpSignature, array("1", "0")) ? (int) $phpSignature : 0;
		$phpDisableSmilies = checkIfSetAndInArray($phpDisableSmilies, array("1", "0")) ? (int) $phpDisableSmilies : 0;
		$phpModCloseThread = checkIfSetAndInArray($phpModCloseThread, array("1", "0")) ? (int) $phpModCloseThread : 0;
		$phpModStickThread = checkIfSetAndInArray($phpModStickThread, array("1", "0")) ? (int) $phpModStickThread : 0;
		$posthandler = new PostDataHandler('insert');
		$posthandler->action = 'thread';
		$data = array(
			"uid" => $this->get_user()->uid,
			"username" => $this->get_user()->username,
			"subject" => $phpSubject,
			"fid" => $phpForumId,
			"prefix" => $phpPrefix,
			"message" => $phpMessage,
			"ipaddress" => $phpIpAddress,
			"icon" => $phpIcon,
			"savedraft" => $phpSaveDraft,
			"options" => array(
				"subscriptionmethod" => $phpSubscriptionMetod,
				"signature" => $phpSignature,
				"disablesmilies" => $phpDisableSmilies,
			)
		);
		if(isset($this->get_user()->is_moderator) && $this->get_user()->is_moderator) {
			$data[] = array(
				"closethread" => $phpModCloseThread,
				"stickthread" => $phpModStickThread
			);
		}
		$posthandler->set_data($data);
		if (!$posthandler->validate_thread()) {
			throw new BadRequestException((object) $posthandler->get_friendly_errors());
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
