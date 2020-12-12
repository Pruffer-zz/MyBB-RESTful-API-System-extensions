<?php
function checkIfSetAndString($var) {
	if (isset($var) && is_string($var)) {
		return true;
	} else {
		return false;
	}
}
function checkIfSetAndBool($var) {
	if (isset($var) && is_bool($var)) {
		return true;
	} else {
		return false;
	}
}
function checkIfSetAndInArray($var, $array) {
	if (isset($var) && in_array($var, $array)) {
		return true;
	} else {
		return false;
	}
}
function checkIfKeySetAndInArray($var, $array) {
	if (isset($var) && array_key_exists($var, $array)) {
		return true;
	} else {
		return false;
	}
}
function checkIfSetAndNumerical($var) {
	if (isset($var) && is_numeric($var)) {
		return true;
	} else {
		return false;
	}
}
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
