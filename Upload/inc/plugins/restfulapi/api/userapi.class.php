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
		switch(strtolower($phpData["action"])) {
			case "list" :
			if($phpData["sort"])
			{
				$phpData["sort"] = strtolower($phpData["sort"]);
			}
			else
			{
				$phpData["sort"] = $mybb->settings['default_memberlist_sortby'];
			}
			switch($phpData["sort"])
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
					$phpData["sort"] = 'username';
					break;
			}
			if($phpData["order"])
			{
				$phpData["order"] = strtolower($phpData["order"]);
			}
			else
			{
				$phpData["order"] = strtolower($mybb->settings['default_memberlist_order']);
			}
			if($phpData["order"] == "ascending" || (!$phpData["order"] && $phpData["sort"] == 'username'))
			{
				$sort_order = "ASC";
				$phpData["order"] = "ascending";
			}
			else
			{
				$sort_order = "DESC";
				$phpData["order"] = "descending";
			}
			$phpData["perpage"] = intval($phpData["perpage"]);
			if($phpData["perpage"] > 0 && $phpData["perpage"] <= 500)
			{
				$per_page = $phpData["perpage"];
			}
			else if($mybb->settings['membersperpage'])
			{
				$per_page = $phpData["perpage"] = intval($mybb->settings['membersperpage']);
			}
			else
			{
				$per_page = $phpData["perpage"] = 20;
			}
			$search_query = '1=1';
			if($phpData["letter"])
			{
				$letter = chr(ord($phpData["letter"]));
				if($phpData["letter"] == -1)
				{
					$search_query .= " AND u.username NOT REGEXP('[a-zA-Z]')";
				}
				else if(strlen($letter) == 1)
				{
					$search_query .= " AND u.username LIKE '".$db->escape_string_like($letter)."%'";
				}
			}
			$search_username = htmlspecialchars_uni(trim($phpData["username"]));
			if($search_username != '')
			{
				$username_like_query = $db->escape_string_like($search_username);
				if($phpData["usernamematch"] == "begins")
				{
					$search_query .= " AND u.username LIKE '".$username_like_query."%'";
				}
				else
				{
					$search_query .= " AND u.username LIKE '%".$username_like_query."%'";
				}
			}
			$search_website = htmlspecialchars_uni($phpData["website"]);
			if(trim($phpData["website"]))
			{
				$search_query .= " AND u.website LIKE '%".$db->escape_string_like($phpData["website"])."%'";
			}
			$query = $db->simple_select("users u", "COUNT(*) AS users", "{$search_query}");
			$num_users = $db->fetch_field($query, "users");
			$page = intval($phpData["page"]);
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
