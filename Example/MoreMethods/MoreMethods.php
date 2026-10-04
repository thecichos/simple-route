<?php

namespace MoreMethods;

use Route\Route;

/**
 * Handles a GET request to the /coolstuff route.
 */
class MoreMethods
{
	#[Route("GET", "/coolstuff")]
	public static function GET_coolstuff()
	{
		?>
		<h1>This is the cool stuff</h1>

		<a href="/Example">Go back</a>
		<?php
	}
}