<?php


use Discovery\Discovery;

require "../vendor/autoload.php";

$discovery = new Discovery(
	["Methods", "MoreMethods"],
	function ($strUrl) {
		return str_replace("/Example", "", $strUrl);
	}
);

$discovery->discover();

$discovery->call();
