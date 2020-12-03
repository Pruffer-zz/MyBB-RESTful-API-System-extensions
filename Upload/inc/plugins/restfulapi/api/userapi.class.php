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
			"description" => "This API exposes the user interface.",
			"default" => "activated"
		);
	}
	/**
	This is where you perform the action when the API is called, the parameter given is an instance of stdClass, this method should return an instance of stdClass.
	*/
	public function action() {
		global $mybb, $db, $cache, $lang;
		$lang->load("api");
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/varfunctions.php";
		require_once MYBB_ROOT . "inc/plugins/restfulapi/functions/jsoncheckfunctions.php";
		$stdClass = new stdClass();
		$phpData = jsonPrecheckAndBodyToArray(file_get_contents("php://input"), "json", $_SERVER["CONTENT_TYPE"], array("action"));
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
						$sortField = "u.regdate";
					break;
					case "lastvisit":
						$sortField = "u.lastactive";
					break;
					case "reputation":
						$sortField = "u.reputation";
					break;
					case "postnum":
						$sortField = "u.postnum";
					break;
					case "referrals":
						$sortField = "u.referrals";
					break;
					default:
						$sortField = "u.username";
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
					$sortOrder = "ASC";
					$phpData["order"] = "ascending";
				}
				else
				{
					$sortOrder = "DESC";
					$phpData["order"] = "descending";
				}
				$phpData["perpage"] = intval($phpData["perpage"]);
				if($phpData["perpage"] > 0 && $phpData["perpage"] <= 500)
				{
					$perPage = $phpData["perpage"];
				}
				else if($mybb->settings['membersperpage'])
				{
					$perPage = $phpData["perpage"] = intval($mybb->settings['membersperpage']);
				}
				else
				{
					$perPage = $phpData["perpage"] = 20;
				}
				$searchQuery = '1=1';
				if($phpData["letter"])
				{
					$letter = chr(ord($phpData["letter"]));
					if($phpData["letter"] == -1)
					{
						$searchQuery .= " AND u.username NOT REGEXP('[a-zA-Z]')";
					}
					else if(strlen($letter) == 1)
					{
						$searchQuery .= " AND u.username LIKE '".$db->escape_string_like($letter)."%'";
					}
				}
				$searchUsername = htmlspecialchars_uni(trim($phpData["username"]));
				if($searchUsername != '')
				{
					$usernameLikeQuery = $db->escape_string_like($searchUsername);
					if($phpData["usernamematch"] == "begins")
					{
						$searchQuery .= " AND u.username LIKE '".$usernameLikeQuery."%'";
					}
					else
					{
						$searchQuery .= " AND u.username LIKE '%".$usernameLikeQuery."%'";
					}
				}
				$searchWebsite = htmlspecialchars_uni($phpData["website"]);
				if(trim($phpData["website"]))
				{
					$searchQuery .= " AND u.website LIKE '%".$db->escape_string_like($phpData["website"])."%'";
				}
				$query = $db->simple_select("users u", "COUNT(*) AS users", "{$searchQuery}");
				$usersNumber = $db->fetch_field($query, "users");
				$page = intval($phpData["page"]);
				if($page && $page > 0)
				{
					$start = ($page - 1) * $perPage;
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
					WHERE {$searchQuery}
					ORDER BY {$sortField} {$sortOrder}
					LIMIT {$start}, {$perPage}
				");
				$returnArray = new stdClass();
				$returnArray->list = array();
				while($user = $db->fetch_array($query)) {
					$returnArray->list[] = $user;
				}
				$returnArray->count = $usersNumber;
				return $returnArray;
			break;
			case "group":
				$usergroups = $cache->read("usergroups");
				return array_values($usergroups);
			break;
			default:
				throw new BadRequestException($lang->api_no_valid_action_specified);
			break;
		}
	}
	
	private function action_list() {
		
	}
}
