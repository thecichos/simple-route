<?php

namespace Methods;

use Route\Route;

class Methods
{

	#[Route("GET", "/")]
	public static function index()
	{
		?>
		<h1>Hello World</h1>
		<a href="./about">This goes to about</a>
		<br />
		<a href="./coolstuff">This goes to cool stuff</a>
		<br>
		<form action="./" method="POST">
			<input type="submit" value="This goes to POST_index">
		</form>
		<?php
	}

	#[Route("GET", "/about")]
	public static function about()
	{
		?>
			<h1>About</h1>
		<?php
	}

	#[Route("GET", "/about/us")]
	public static function about_us()
	{
		?>
		<h1>About us</h1>
		<?php
	}

	#[Route("POST", "/")]
	public static function POST_index()
	{
		?>
			<h1>index but post</h1>
		<?php
	}

}