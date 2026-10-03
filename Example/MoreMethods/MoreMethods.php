<?php

namespace MoreMethods;

use Route\Route;

class MoreMethods
{
	#[Route("GET", "/coolstuff")]
	public static function GET_coolstuff()
	{
		?>
		<h1>This is the cool stuff</h1>

		<a href="./">Go back</a>
		<?php
	}
}