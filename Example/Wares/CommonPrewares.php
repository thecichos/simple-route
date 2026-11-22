<?php

namespace Wares;

class CommonPrewares
{
	public static function logincheck(?bool $a = null) : void
	{
		if (!$a) {
			header("Location: /login");
			exit;
		} else {
			echo "Login successful";
		}
	}
}