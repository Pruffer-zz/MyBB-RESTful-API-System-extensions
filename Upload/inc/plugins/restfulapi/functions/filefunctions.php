<?php
function checkIfTraversal(string $path, string $location) {
	$realPath = realpath($path);
	$realLocation = realpath($location);
	if ($realPath === false || strpos($realPath, $realLocation) !== 0) {
		return false;
	} else {
		return true;
	}
}
function checkIfFilenameDirectory(string $filenamePath, string $locationPath) {
	if ($filenamePath === false || $filenamePath !== $locationPath) {
		return false;
	} else {
		return true;
	}
}
function checkFileRename(string $location, string $filename, bool $overwrite) {
	if (file_exists($location.$filename) && $overwrite === false) {
		$filename = time().".".$filename;
		while (file_exists($location.$filename)) {
			$filename = substr(md5(microtime()),rand(0,26),5).time().".".$filename;
		}
	}
	return $filename;
}
?>
