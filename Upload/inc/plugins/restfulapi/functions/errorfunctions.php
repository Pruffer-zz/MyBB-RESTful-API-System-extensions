<?php
function throwBadRequestException (string $error) {
	throw new BadRequestException($error);
}
function throwUnauthorizedException (string $error) {
	throw new UnauthorizedException($error);
}
?>
