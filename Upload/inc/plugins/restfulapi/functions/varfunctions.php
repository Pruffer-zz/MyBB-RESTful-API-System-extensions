<?php
function checkIfSetAndString($var) {
	if (isset($var) && is_string($var)) {
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
function checkIfSetAndNumerical($var) {
	if (isset($var) && is_numeric($var)) {
		return true;
	} else {
		return false;
	}
}
?>

