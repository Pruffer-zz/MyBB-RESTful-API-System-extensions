<?php
function checkIfSetAndString($var) {
	if (isset($var) && is_string($var)) {
		return true;
	} else {
		return false;
	}
}
function returnError($message) {
	return "Unsuccessful: ".$message;
}
function returnSuccess($message) {
	return "Successful: ".$message;
}
?>
