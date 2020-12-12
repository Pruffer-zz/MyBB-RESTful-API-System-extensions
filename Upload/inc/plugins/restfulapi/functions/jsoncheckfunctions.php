<?php
	function jsonPrecheckAndBodyToArray(string $rawBody, string $requiredContentType, string $contentType, array $requiredStringKeys = array(), $requiredBoolKeys = null) {
		global $lang;
		$lang->load("api");
		if (!($body = checkIfJson($rawBody))) {
			throwBadRequestException($lang->api_json_invalid);
		}
		switch ($requiredContentType) {
			case "json":
				if ($contentType !== "application/json") {
					throwBadRequestException($lang->api_incorrect_content_type."\"application/json\"");
				}
			break;
			case "post":
				if (!strpos($contentType, "multipart/form-data") === 0) {
					throwBadRequestException($lang->api_incorrect_content_type."\"multipart/form-data\"");
				}
			break;
		}
		try {
			foreach($body as $key=>$data) {
				$phpData[$key] = $data;
			}
		}
		catch (Exception $e) {
			throwBadRequestException($lang->api_json_read_error);
		}
		if (!empty($requiredStringKeys)) {
			foreach ($requiredStringKeys as $key) {
				if (!checkIfSetAndString($phpData[$key])) {
					throwBadRequestException($lang->api_key_missing.implode(", ", $requiredStringKeys));
				}
			}
		}
		if (!empty($requiredBoolKeys)) {
			foreach($requiredBoolKeys as $key) {
				if (!checkIfSetAndBool($phpData[$key])) {
					throwBadRequestException($lang->api_key_missing.implode(", ", $requiredBoolKeys));
				}
			}
		}
		return $phpData;
	}
?>
