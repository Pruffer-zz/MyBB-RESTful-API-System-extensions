<?php
function checkIfJson($body) {
	if ($return = json_decode($body)) {
		return $return;
	} else {
		return false;
	}
}
function getKeyValue($key, $body) {
	if ($returnKey = $body->$key) {
		return $returnKey;
	} else {
		return false;
	}
}
?>
