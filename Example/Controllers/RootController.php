<?php

namespace Controllers;

use Controller\Controller;
use Route\Route;

/**
 * This is the root controller, this handles all the methods that are available on root I.E: website.com/
 */
class RootController extends Controller
{

	#[Route("GET", "/")]
	public static function root(): void
	{
		echo __CLASS__;
	}

	// this is not allowed!!
	// We have a controller name example
	// #[Route("GET", "/Example")]
	public static function Example(): void
	{

	}
}