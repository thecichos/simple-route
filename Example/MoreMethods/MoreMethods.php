<?php

namespace MoreMethods;

use Middleware\Middleware;
use Route\Route;

/**
 * Handles a GET request to the /coolstuff route.
 */
class MoreMethods
{

	public static function pre_do(): void
	{
		echo "pre_do";
	}

	public static function post_do(): void
	{
		echo "post_do";
	}

	public static function header()
	{
		?>
		<html>
		<head>
			<title>More Methods</title>
		</head>
		<body>
		the body goes here
		<br />
		<?php
	}

	public static function footer()
	{
		?>
		<br />The footer goes here
		</body>
		</html>
		<?php
	}

	#[Route("GET", "/coolstuff/{param}")]
	#[Middleware(MoreMethods::class, "header", null)]
	#[Middleware(MoreMethods::class, "pre_do", null)]
	#[Middleware(MoreMethods::class, "post_do", null, "after")]
	#[Middleware(MoreMethods::class, "footer", null, "after")]
	public static function GET_coolstuff(string $param): void
	{
		?>
		<h1>This is the cool stuff</h1>

		<a href="/Example">Go back</a>
		<?php
	}

	#[Route("GET", "/stuffWithMultipleParams/{param}/{param2}")]
	public static function GET_stuffWithMultipleParams(int $param, string $param2): void
	{
		?>
		<h1>This is the cool stuff</h1>

		<a href="/Example">Go back</a>
		<?php
	}
}