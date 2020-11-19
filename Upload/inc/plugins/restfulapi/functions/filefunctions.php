<?php
$fileFunctionsLocation = "/md0/user/usercontent";
function checkIfTraversal($path, $location) {
	$realPath = realpath($path);
	$realLocation = realpath($location);
	if ($realPath === false || strpos($realPath, $realLocation) !== 0) {
		return false;
	} else {
		return true;
	}
}
function checkIfFilenameDirectory($filenamePath, $locationPath) {
	if (realpath($filenamePath) === false || realpath($filenamePath) !== realpath($locationPath)) {
		return false;
	} else {
		return true;
	}
}
?>
