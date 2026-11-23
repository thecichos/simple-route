<?php

namespace Middleware;

use Attribute;

/* @TODO Make a middleware interface that requires a public function called handle which allows a middleware to called anywhere */
/* @TODO Make it possible to add middleware to classes, which would allow for a middleware to be called on every endpoint in a controller */
/* @TODO make it so that middleware can be made global, so that every endpoint will use the middleware */

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Postware
{

	public function __construct(
		public string $class,
		public string $method,
		public ?array $args = null
	)
	{

	}

}