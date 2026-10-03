<?php


use Discovery\Discovery;

require "../vendor/autoload.php";

$discovery = new Discovery(
	"Methods",
	function ($strUrl) {
		return str_replace("/Example", "", $strUrl);
	}
);
$discovery->discover();


try {
	$discovery->call();
} catch(Exception) {
	$discovery = new Discovery(
		"MoreMethods",
		function ($strUrl) {
			return str_replace("/Example", "", $strUrl);
		}
	);
	$discovery->discover();
	$discovery->call();
}
