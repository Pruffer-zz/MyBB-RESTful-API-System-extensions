<?php
$fileFunctionsLocation = "/path/to/fun/files"; // Make sure to change this part, and include a trailing slash
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
