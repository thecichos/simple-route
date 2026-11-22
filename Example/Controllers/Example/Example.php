<?php

namespace Controllers\Example;

use Controller\Controller;
use Middleware\Preware;
use Route\Route;
use Wares\CommonPrewares;

class Example extends Controller
{
	#[Route("GET", "/")]
	public static function root()
	{
		echo __CLASS__;
	}

	#[Route("GET", "/test/{i}/{?t}")]
	public static function test(int $i, string $t = "")
	{
		echo __CLASS__." TEST ".$i." ".$t;
	}

	#[Route("GET", "/login/{a}")]
	#[Preware(CommonPrewares::class, "logincheck")]
	public static function login(?bool $a = null) : void
	{

	}
}