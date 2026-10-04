<?php

namespace Methods;

use AutoDocumentation\AutoDocumentation;
use Route\Route;

/**
 * Handles the route for the root endpoint ("/") with a GET request.
 * Displays a "Hello World" page with links to documentation and other resources.
 * Provides a form submission option for POST requests to the same endpoint.
 */
class Methods
{

	/**
	 * Handles the HTTP GET request for the root route.
	 *
	 * @return void
	 */
	#[Route("GET", "/")]
	public static function index()
	{
		?>
		<h1>Hello World</h1>
		<a href="/Example/Docs">Documentation</a>
		<br />
		<a href="/Example/coolstuff">This goes to cool stuff</a>
		<br>
		<form action="/Example" method="POST">
			<input type="submit" value="This goes to POST_index">
		</form>
		<?php
	}

	/**
	 * Executes the private method that outputs a specific string.
	 *
	 * @return void
	 */
	private static function privateMethod() {
		echo "super secret sauce";
	}

	#[Route("GET", "/Docs")]
	public static function Documentation()
	{
		$documentationPage = new AutoDocumentation();

		require_once "./MoreMethods/MoreMethods.php";

		$documentationPage->registerTypes(["Methods\Methods", "MoreMethods\MoreMethods"]);

		echo $documentationPage->toHTML();
	}

	#[Route("POST", "/")]
	public static function POST_index()
	{
		?>
			<h1>index but post</h1>
		<br>
		<a href="/Example">Go back</a>
		<?php
	}

}