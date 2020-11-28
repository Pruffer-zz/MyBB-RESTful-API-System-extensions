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
class UserAPI extends RESTfulAPI {
	
	public function info() {
		return array(
			"name" => "User",
			"description" => "This API exposes user interface.",
			"default" => "activated"
		);
	}
	/**
	This is where you perform the action when the API is called, the parameter given is an instance of stdClass, this method should return an instance of stdClass.
	*/
	public function action() {
		global $mybb, $db, $cache;
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/jsonfunctions.php";
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/varfunctions.php";
		$stdClass = new stdClass();
		$rawBody = file_get_contents("php://input");
		if (!($body = checkIfJson($rawBody))) {
			throw new BadRequestException("Invalid JSON data.");
		}
		$phpAction = getKeyValue("action", $body);
		$phpSort = getKeyValue("sort", $body);
		$phpOrder = getKeyValue("order", $body);
		$phpLetter = getKeyValue("letter", $body);
		$phpPerPage = getKeyValue("perpage", $body);
		$phpUsername = getKeyValue("username", $body);
		$phpUsernameMatch = getKeyValue("usernamematch", $body);
		$phpWebsite = getKeyValue("website", $body);
		$phpPage = getKeyValue("page", $body);
		$phpContentType = $_SERVER["CONTENT_TYPE"];
		if ($phpContentType !== "application/json") {
			$error = ("\"content-type\" header missing, or not \"application/json\".");
		}
		if(!checkIfSetAndString($phpAction)) {
			$error = ("\"action\" key missing.");
		}
		if ($error) {
			throw new BadRequestException($error);
		}
		switch(strtolower($phpAction)) {
			case "list" :
			if($phpSort)
			{
				$phpSort = strtolower($phpSort);
			}
			else
			{
				$phpSort = $mybb->settings['default_memberlist_sortby'];
			}
			switch($phpSort)
			{
				case "regdate":
					$sort_field = "u.regdate";
					break;
				case "lastvisit":
					$sort_field = "u.lastactive";
					break;
				case "reputation":
					$sort_field = "u.reputation";
					break;
				case "postnum":
					$sort_field = "u.postnum";
					break;
				case "referrals":
					$sort_field = "u.referrals";
					break;
				default:
					$sort_field = "u.username";
					$phpSort = 'username';
					break;
			}
			if($phpOrder)
			{
				$phpOrder = strtolower($phpOrder);
			}
			else
			{
				$phpOrder = strtolower($mybb->settings['default_memberlist_order']);
			}
			if($phpOrder == "ascending" || (!$phpOrder && $phpSort == 'username'))
			{
				$sort_order = "ASC";
				$phpOrder = "ascending";
			}
			else
			{
				$sort_order = "DESC";
				$phpOrder = "descending";
			}
			$phpPerPage = intval($phpPerPage);
			if($phpPerPage > 0 && $phpPerPage <= 500)
			{
				$per_page = $phpPerPage;
			}
			else if($mybb->settings['membersperpage'])
			{
				$per_page = $phpPerPage = intval($mybb->settings['membersperpage']);
			}
			else
			{
				$per_page = $phpPerPage = 20;
			}
			$search_query = '1=1';
			if($phpLetter)
			{
				$letter = chr(ord($phpLetter));
				if($phpLetter == -1)
				{
					$search_query .= " AND u.username NOT REGEXP('[a-zA-Z]')";
				}
				else if(strlen($letter) == 1)
				{
					$search_query .= " AND u.username LIKE '".$db->escape_string_like($letter)."%'";
				}
			}
			$search_username = htmlspecialchars_uni(trim($phpUsername));
			if($search_username != '')
			{
				$username_like_query = $db->escape_string_like($search_username);
				if($phpUsernameMatch == "begins")
				{
					$search_query .= " AND u.username LIKE '".$username_like_query."%'";
				}
				else
				{
					$search_query .= " AND u.username LIKE '%".$username_like_query."%'";
				}
			}
			$search_website = htmlspecialchars_uni($phpWebsite);
			if(trim($phpWebsite))
			{
				$search_query .= " AND u.website LIKE '%".$db->escape_string_like($phpWebsite)."%'";
			}
			$query = $db->simple_select("users u", "COUNT(*) AS users", "{$search_query}");
			$num_users = $db->fetch_field($query, "users");
			$page = intval($phpPage);
			if($page && $page > 0)
			{
				$start = ($page - 1) * $per_page;
			}
			else
			{
				$start = 0;
				$page = 1;
			}
			$query = $db->query("
				SELECT u.*, f.*
				FROM ".TABLE_PREFIX."users u
				LEFT JOIN ".TABLE_PREFIX."userfields f ON (f.ufid=u.uid)
				WHERE {$search_query}
				ORDER BY {$sort_field} {$sort_order}
				LIMIT {$start}, {$per_page}
			");
			$return_array = new stdClass();
			$return_array->list = array();
			while($user = $db->fetch_array($query)) {
				$return_array->list[] = $user;
			}
			$return_array->count = $num_users;
			return $return_array;
			break;
			case "group" :
			$usergroups = $cache->read("usergroups");
			return array_values($usergroups);
			break;
			default :
			break;
		}
		throw new BadRequestException("No valid option given in the URL.");
	}
	
	private function action_list() {
		
	}
}
