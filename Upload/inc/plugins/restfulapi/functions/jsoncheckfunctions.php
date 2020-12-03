<?php
	function jsonPrecheckAndBodyToArray(string $rawBody, string $requiredContentType, string $contentType, array $requiredKeys = array()) {
		global $lang;
		$lang->load("api");
		$phpData = array();
		if (!($body = checkIfJson($rawBody))) {
			throw new BadRequestException($lang->api_json_invalid);
		}
		switch ($requiredContentType) {
			case "json":
				$rawBody = file_get_contents("php://input");
				if ($contentType !== "application/json") {
					throw new BadRequestException($lang->api_incorrect_content_type."\"application/json\"");
				}
			break;
			case "post":
				$rawBody = $_POST["json"];
				if (!strpos($contentType, "multipart/form-data") === 0) {
					throw new BadRequestException($lang->api_incorrect_content_type."\"multipart/form-data\"");
				}
			break;
		}
		try {
				foreach($body as $key=>$data) {
					$phpData[$key] = $data;
				}
				if (!empty($requiredKeys)) {
					foreach ($requiredKeys as $key) {
						if (!checkIfSetAndString($phpData[$key], $key)) {
							throw new BadRequestException($lang->api_key_missing.implode(", ", $requiredKeys));
						}
					}
				}
			return $phpData;
		}
		catch (Exception $e) {
			return $lang->api_json_read_error;
		}
	}
?>
