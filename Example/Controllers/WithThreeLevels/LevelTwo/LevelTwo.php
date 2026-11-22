<?php

namespace Controllers\WithThreeLevels\LevelTwo;

use Controller\Controller;
use Route\Route;

class LevelTwo extends Controller
{
	#[Route("GET", "/")]
	public static function root()
	{
		echo __CLASS__;
	}
}