<?php

namespace Controllers\WithThreeLevels;

use Controller\Controller;
use Middleware\Postware;
use Middleware\Preware;
use Wares\CommonPostware;
use Route\Route;

class WithThreeLevels extends Controller
{

	#[Route("GET", "/")]
	#[Postware(CommonPostware::class, "doingTheStuff")]
	#[Preware(CommonPostware::class, "doingTheStuff")]
	public static function root()
	{
		echo __CLASS__;
	}
}