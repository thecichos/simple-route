<?php

namespace NamedControllers;

use Controller\Controller;
use Controller\ControllerPrefix;
use Route\Route;

#[ControllerPrefix('/admin')]
class AdminController extends Controller
{

	#[Route("GET", "/{?int}")]
	public static function root(?int $id = null)
	{
		echo "<pre>";
		var_dump("This is the admin root", $id);
		echo "</pre>";
	}

	#[Route("GET", "/dashboard")]
	public static function dashboard()
	{
		echo "<pre>";
		var_dump("This is the admin dashboard");
		echo "</pre>";
	}

}