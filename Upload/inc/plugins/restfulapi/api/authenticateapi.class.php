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
class AuthenticateAPI extends RESTfulAPI {
	
	public function info() {
		return array(
			"name" => "Authentication",
			"description" => "This API exposes authentication interface.",
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
		$phpSessionId = getKeyValue("sessionid", $body);
		$phpContentType = $_SERVER["CONTENT_TYPE"];
		if ($phpContentType !== "application/json") {
			$error = ("\"content-type\" header missing, or not \"application/json\".");
		}
		if(!checkIfSetAndString($phpSessionId)) {
			$error = ("\"action\" key missing.");
		}
		if ($error) {
			throw new BadRequestException($error);
		}
		if($this->is_authenticated()) {
			return $this->get_user();
		}
		elseif(isset($phpSessionId) && is_string($phpSessionId)) {
			$sid = $db->escape_string($phpSessionId);
			$query = $db->query("SELECT s.uid FROM " . TABLE_PREFIX . "sessions s WHERE s.sid = '{$sid}'");
			$result = $db->fetch_array($query);
			if(empty($result)) {
				throw new UnauthorizedException("Not connected.");
			}
			else {
				$uid = $result['uid'];
				$query = $db->query("
					SELECT u.*, f.*
					FROM ".TABLE_PREFIX."users u
					LEFT JOIN ".TABLE_PREFIX."userfields f ON (f.ufid=u.uid)
					WHERE u.uid='$uid'
					LIMIT 1
				");
				$user = (object) $db->fetch_array($query);
				if(empty($user)) {
					throw new UnauthorizedException("Not connected");
				}
				$user->ismoderator = is_moderator("", "", $uid);
				return $user;
			}
		}
		else {
			throw new UnauthorizedException("Not connected.");
		}
	}
}
